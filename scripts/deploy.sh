#!/usr/bin/env bash

set -euo pipefail

# ==============================================================================
# SCRIPT DE DEPLOY & ROLLBACK ATÔMICO / IMUTÁVEL (Laravel + Docker)
# ==============================================================================

ACTION="${1:-deploy}"
ENV_INPUT="${2:-hom}"

# 1. Trava contra execuções concorrentes (Concurrency Lock)
LOCK_FILE="/tmp/blink-deploy-${ENV_INPUT}.lock"
exec 200>"${LOCK_FILE}"
if ! flock -n 200; then
    echo "❌ ERRO: Já existe um deploy ou rollback em execução para [${ENV_INPUT}]."
    exit 1
fi

# 2. Mapeamento explícito de ambiente
case "${ENV_INPUT}" in
    prod|production|main)
        ENVIRONMENT="prod"
        PROJECT_NAME="blink-prod"
        COMPOSE_FILE="docker-compose.prod.yml"
        ;;
    hom|staging|dev)
        ENVIRONMENT="hom"
        PROJECT_NAME="blink-hom"
        COMPOSE_FILE="docker-compose.hom.yml"
        ;;
    *)
        echo "❌ Ambiente inválido: '${ENV_INPUT}'. Use 'hom' ou 'prod'."
        exit 1
        ;;
esac

# Identificação Git & Metadata Avançado
GIT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "nogit")
GIT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")
GIT_AUTHOR=$(git log -1 --format='%an' 2>/dev/null || echo "unknown")
GIT_REMOTE=$(git config --get remote.origin.url 2>/dev/null || echo "unknown")
DOCKER_VER=$(docker --version 2>/dev/null || echo "unknown")
KERNEL_VER=$(uname -r 2>/dev/null || echo "unknown")
RELEASE_TAG="$(date +%Y%m%d%H%M%S)-${GIT_COMMIT}"

# Caminhos Base
BASE_DIR="/var/www/${PROJECT_NAME}"
RELEASES_DIR="${BASE_DIR}/releases"
SHARED_DIR="${BASE_DIR}/shared"
LOGS_DIR="${SHARED_DIR}/logs/deploy"
CURRENT_LINK="${BASE_DIR}/current"
NEW_RELEASE_DIR="${RELEASES_DIR}/${RELEASE_TAG}"

# Redirecionamento de Logs para Arquivo e Terminal em Tempo Real
mkdir -p "${LOGS_DIR}"
DEPLOY_LOG_FILE="${LOGS_DIR}/deploy-$(date +%Y%m%d).log"
exec > >(tee -a "${DEPLOY_LOG_FILE}") 2>&1

echo "======================================================================"
echo "▶ Execute: ${0} ${ACTION} ${ENV_INPUT} | Data: $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
echo "======================================================================"

# Exportação de Variáveis Globais para o Docker Compose
export PROJECT_NAME="${PROJECT_NAME}"
export IMAGE_TAG="${IMAGE_TAG:-${GIT_COMMIT}}"
export DOCKER_NETWORK="${DOCKER_NETWORK:-${PROJECT_NAME}-net}"

# ------------------------------------------------------------------------------
# HELPER: ATUALIZAÇÃO SEGURA DO JSON VIA PYTHON3 (Não aborta o script em falha)
# ------------------------------------------------------------------------------
update_json_status() {
    local json_file="$1"
    local status_val="$2"

    if [ ! -f "${json_file}" ]; then return 0; fi

    if command -v python3 >/dev/null 2>&1; then
        python3 - "${json_file}" "${status_val}" << 'EOF' || true
import json, sys
try:
    json_file, status_val = sys.argv[1], sys.argv[2]
    with open(json_file, 'r') as f:
        data = json.load(f)
    data['status'] = status_val
    with open(json_file, 'w') as f:
        json.dump(data, f, indent=2)
except Exception as e:
    print(f"⚠️ AVISO: Não foi possível atualizar o status no release.json: {e}")
EOF
    fi
}

# ------------------------------------------------------------------------------
# HELPER: DESCOBERTA DA ÚLTIMA RELEASE "SUCCESS" (Ordenação Alfanumérica)
# ------------------------------------------------------------------------------
find_last_successful_release() {
    local active_rel
    active_rel=$(readlink -f "${CURRENT_LINK}" 2>/dev/null || echo "")

    find "${RELEASES_DIR}" -mindepth 1 -maxdepth 1 -type d 2>/dev/null \
        | sort -r \
        | while read -r dir; do
            if [ "${dir}" = "${active_rel}" ]; then
                continue
            fi

            local json_path="${dir}/release.json"
            if [ -f "${json_path}" ] && grep -q '"status": "SUCCESS"' "${json_path}" 2>/dev/null; then
                echo "${dir}"
                return 0
            fi
        done
}

# ------------------------------------------------------------------------------
# TRAP DE LIMPEZA COM DUMP DE LOGS TIMESTAMPEADOS E INSPECT COMPLETO
# ------------------------------------------------------------------------------
cleanup_on_failure() {
    local exit_code=$?
    if [ $exit_code -ne 0 ] && [ "${ACTION}" = "deploy" ] && [ -d "${NEW_RELEASE_DIR}" ]; then
        echo "🚨 FALHA DETECTADA (Código ${exit_code}). Interrompendo e executando limpeza..."

        update_json_status "${NEW_RELEASE_DIR}/release.json" "FAILED"

        local compose_file="${NEW_RELEASE_DIR}/${COMPOSE_FILE}"
        if [ -f "${compose_file}" ]; then
            echo "💾 Dumping logs com timestamps de todos os serviços..."
            docker compose --env-file "${SHARED_DIR}/.env" -f "${compose_file}" logs --timestamps --tail=200 > "${NEW_RELEASE_DIR}/failed-stack-app.log" 2>&1 || true
            docker compose --env-file "${SHARED_DIR}/.env" -f "${compose_file}" down --remove-orphans 2>/dev/null || true
        fi

        if [ "$(readlink -f "${CURRENT_LINK}" 2>/dev/null)" = "${NEW_RELEASE_DIR}" ]; then
            echo "⚠️ Revertendo symlink para a última release estável..."
            LAST_GOOD=$(find_last_successful_release)
            if [ -n "${LAST_GOOD}" ]; then
                ln -sfn "${LAST_GOOD}" "${CURRENT_LINK}"
            fi
        fi

        echo "🧹 Removendo pasta da release abortada: ${NEW_RELEASE_DIR}"
        rm -rf "${NEW_RELEASE_DIR}"
    fi
    exit $exit_code
}
trap cleanup_on_failure EXIT INT TERM ERR

# ------------------------------------------------------------------------------
# FUNÇÃO REUTILIZÁVEL: HealthCheck (Running + Health + OOM + HTTP)
# ------------------------------------------------------------------------------
run_healthcheck() {
    local target_dir="$1"
    local compose_file="${target_dir}/${COMPOSE_FILE}"

    echo "⏳ Identificando ID do container da aplicação..."
    APP_CID=$(docker compose --env-file "${SHARED_DIR}/.env" -f "${compose_file}" ps -q app 2>/dev/null || true)

    if [ -z "${APP_CID}" ]; then
        echo "❌ ERRO CRÍTICO: Container 'app' não localizado."
        return 1
    fi

    echo "⏳ Verificando integridade e estado operacional do container [${APP_CID:0:12}]..."
    local MAX_WAIT=120
    local WAIT=0
    local HEALTHY=false

    while [ "$WAIT" -lt "$MAX_WAIT" ]; do
        # 1. Validação do Estado Básico do Container
        STATE_STATUS=$(docker inspect --format='{{.State.Status}}' "${APP_CID}" 2>/dev/null || echo "exited")
        if [ "${STATE_STATUS}" != "running" ]; then
            echo "❌ ERRO CRÍTICO: Container não está rodando (Estado atual: '${STATE_STATUS}')."
            docker compose --env-file "${SHARED_DIR}/.env" -f "${compose_file}" ps -q | xargs docker inspect > "${target_dir}/failed-stack-inspect.json" 2>&1 || true
            return 1
        fi

        # 2. Validação de OOMKilled
        OOM_KILLED=$(docker inspect --format='{{.State.OOMKilled}}' "${APP_CID}" 2>/dev/null || echo "false")
        if [ "${OOM_KILLED}" = "true" ]; then
            echo "❌ ERRO FATAL: Container foi finalizado por falta de memória (OOMKilled)."
            docker compose --env-file "${SHARED_DIR}/.env" -f "${compose_file}" ps -q | xargs docker inspect > "${target_dir}/failed-stack-inspect.json" 2>&1 || true
            return 1
        fi

        # 3. Validação do Healthcheck Nativo do Docker
        HEALTH_STATUS=$(docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}no-healthcheck{{end}}' "${APP_CID}" 2>/dev/null || echo "unknown")

        case "$HEALTH_STATUS" in
            healthy)
                echo "✅ Container [${APP_CID:0:12}] está rodando e saudável (healthy)!"
                HEALTHY=true
                break
                ;;
            unhealthy)
                echo "❌ ERRO CRÍTICO: Container atingiu o estado UNHEALTHY!"
                docker compose --env-file "${SHARED_DIR}/.env" -f "${compose_file}" ps -q | xargs docker inspect > "${target_dir}/failed-stack-inspect.json" 2>&1 || true
                return 1
                ;;
            *)
                echo "  -> Status: running | Health: [${HEALTH_STATUS}]. Aguardando... (${WAIT}s/${MAX_WAIT}s)"
                ;;
        esac

        sleep 2
        WAIT=$((WAIT+2))
    done

    if [ "$HEALTHY" = false ]; then
        echo "❌ TIMEOUT: Container não atingiu o estado saudável no tempo limite."
        docker compose --env-file "${SHARED_DIR}/.env" -f "${compose_file}" ps -q | xargs docker inspect > "${target_dir}/failed-stack-inspect.json" 2>&1 || true
        return 1
    fi

    # 4. Checagem HTTP na rota /up com Retry
    HOST_PORT=$(docker compose --env-file "${SHARED_DIR}/.env" -f "${compose_file}" port nginx 80 2>/dev/null | cut -d: -f2 || true)
    if [ -n "${HOST_PORT}" ]; then
        echo "🔍 Validando resposta HTTP em http://localhost:${HOST_PORT}/up..."
        if curl --silent --fail --retry 3 --retry-delay 1 --max-time 5 "http://localhost:${HOST_PORT}/up" > /dev/null; then
            echo "🎉 Endpoint /up respondeu HTTP 200 OK!"
        else
            echo "⚠️ AVISO: A rota /up não retornou status 200 OK."
        fi
    fi

    return 0
}

# ------------------------------------------------------------------------------
# RECURSO: ROLLBACK COM EXTRAÇÃO ROBUSTA DA IMAGE_TAG VIA PYTHON
# ------------------------------------------------------------------------------
if [ "${ACTION}" = "rollback" ]; then
    echo "⏪ Iniciando processo de Rollback no ambiente: [${ENVIRONMENT}]..."

    ACTIVE_RELEASE=$(readlink -f "${CURRENT_LINK}" 2>/dev/null || echo "")
    TARGET_ROLLBACK_DIR=$(find_last_successful_release)

    if [ -z "${TARGET_ROLLBACK_DIR}" ] || [ ! -d "${TARGET_ROLLBACK_DIR}" ]; then
        echo "❌ ERRO FATAL: Nenhuma release anterior com status 'SUCCESS' foi encontrada."
        exit 1
    fi

    echo "🎯 Release destino selecionada: $(basename "${TARGET_ROLLBACK_DIR}")"
    TARGET_COMPOSE="${TARGET_ROLLBACK_DIR}/${COMPOSE_FILE}"

    # Extração robusta do image_tag do release.json usando Python
    ROLLBACK_IMAGE_TAG=""
    if [ -f "${TARGET_ROLLBACK_DIR}/release.json" ] && command -v python3 >/dev/null 2>&1; then
        ROLLBACK_IMAGE_TAG=$(python3 -c "import json; print(json.load(open('${TARGET_ROLLBACK_DIR}/release.json')).get('image_tag', ''))" 2>/dev/null || echo "")
    fi

    if [ -n "${ROLLBACK_IMAGE_TAG}" ]; then
        echo "📌 Replicando IMAGE_TAG da release de origem: ${ROLLBACK_IMAGE_TAG}"
        export IMAGE_TAG="${ROLLBACK_IMAGE_TAG}"
    fi

    if [ -d "${ACTIVE_RELEASE}" ]; then
        echo "🛑 Desligando serviços da release com falhas..."
        ACTIVE_COMPOSE="${ACTIVE_RELEASE}/${COMPOSE_FILE}"
        if [ -f "${ACTIVE_COMPOSE}" ]; then
            docker compose --env-file "${SHARED_DIR}/.env" -f "${ACTIVE_COMPOSE}" down --remove-orphans 2>/dev/null || true
        fi
    fi

    echo "🔗 Alterando symlink 'current'..."
    ln -sfn "${TARGET_ROLLBACK_DIR}" "${CURRENT_LINK}"

    echo "🚀 Subindo containers da release restaurada..."
    docker compose --env-file "${SHARED_DIR}/.env" -f "${TARGET_COMPOSE}" up -d --wait --wait-timeout 120 --remove-orphans

    echo "🔍 Validando saúde do ambiente pós-rollback..."
    if run_healthcheck "${TARGET_ROLLBACK_DIR}"; then
        echo "✨ Rollback concluído com sucesso para: $(basename "${TARGET_ROLLBACK_DIR}")!"
        exit 0
    else
        echo "❌ ERRO CRÍTICO: A release de rollback falhou na verificação de saúde!"
        exit 1
    fi
fi

# ------------------------------------------------------------------------------
# FLUXO DE DEPLOY
# ------------------------------------------------------------------------------
echo "🚀 Iniciando deploy no ambiente: [${ENVIRONMENT}] (${PROJECT_NAME}) - Release: ${RELEASE_TAG}"

# 1. Validação de Alterações Pendentes no Git Local
if command -v git >/dev/null 2>&1 && git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    if ! git diff --quiet HEAD 2>/dev/null; then
        echo "❌ ABORTANDO: Existem alterações não commitadas no repositório local. Faça commit ou stash antes de implantar."
        exit 1
    fi
fi

mkdir -p "${RELEASES_DIR}"
mkdir -p "${SHARED_DIR}"

# 2. Validação Prévia de Espaço em Disco
FREE_DISK_MB=$(df -Pm "${BASE_DIR}" 2>/dev/null | awk 'NR==2 {print $4}' || echo "0")
if [ "${FREE_DISK_MB}" -lt 1024 ]; then
    echo "❌ ABORTANDO: Espaço em disco insuficiente em ${BASE_DIR} (${FREE_DISK_MB} MB livres, mínimo: 1024 MB)."
    exit 1
fi

# 3. Validação Estrita do .env
if [ ! -f "${SHARED_DIR}/.env" ]; then
    echo "❌ ABORTANDO: Arquivo de ambiente '${SHARED_DIR}/.env' ausente."
    exit 1
fi

REQUIRED_KEYS=("APP_KEY" "DB_PASSWORD" "REDIS_PASSWORD")
for key in "${REQUIRED_KEYS[@]}"; do
    VAL=$(awk -F '=' -v k="$key" '$1 ~ "^[[:space:]]*"k"[[:space:]]*$" {gsub(/^[[:space:]]*["\']?|["\']?[[:space:]]*$/, "", $2); print $2}' "${SHARED_DIR}/.env")
    if [ -z "${VAL}" ]; then
        echo "❌ ERRO DE CONFIGURAÇÃO: A variável '${key}' não está preenchida em '${SHARED_DIR}/.env'."
        exit 1
    fi
done

# 4. Sincronização dos Arquivos para a Nova Release
echo "📋 Sincronizando arquivos do projeto para a release [${RELEASE_TAG}]..."
mkdir -p "${NEW_RELEASE_DIR}"

rsync -a --delete \
         --exclude='.git' \
         --exclude='.github' \
         --exclude='.idea' \
         --exclude='.vscode' \
         --exclude='tests' \
         --exclude='docker-compose*.override.yml' \
         --exclude='node_modules' \
         --exclude='vendor' \
         --exclude='storage' \
         --exclude='.env' \
         ./ "${NEW_RELEASE_DIR}/"

NEW_COMPOSE_PATH="${NEW_RELEASE_DIR}/${COMPOSE_FILE}"

if [ ! -f "${NEW_COMPOSE_PATH}" ]; then
    echo "❌ ERRO: Arquivo '${COMPOSE_FILE}' não existe na nova release."
    exit 1
fi

# 5. Validação Prévia da Sintaxe do Compose
echo "🔍 Validando configuração do Docker Compose..."
if ! docker compose --env-file "${SHARED_DIR}/.env" -f "${NEW_COMPOSE_PATH}" config -q; then
    echo "❌ ERRO DE SINTAXE: O arquivo '${COMPOSE_FILE}' possui configurações inválidas."
    exit 1
fi

# 6. Cálculo do SHA256 Checksum Relativo da Release
echo "🔏 Gerando checksum relativo de integridade..."
RELEASE_CHECKSUM=$(cd "${NEW_RELEASE_DIR}" && find . -type f ! -name "release.json" -exec sha256sum {} + | sort | sha256sum | awk '{print $1}')

# 7. Geração do release.json Completo
cat <<EOF > "${NEW_RELEASE_DIR}/release.json"
{
  "release": "${RELEASE_TAG}",
  "status": "PENDING",
  "image_tag": "${IMAGE_TAG}",
  "git_commit": "${GIT_COMMIT}",
  "branch": "${GIT_BRANCH}",
  "author": "${GIT_AUTHOR}",
  "git_remote": "${GIT_REMOTE}",
  "environment": "${ENVIRONMENT}",
  "docker_image": "${PROJECT_NAME}-app:${IMAGE_TAG}",
  "docker_version": "${DOCKER_VER}",
  "kernel_version": "${KERNEL_VER}",
  "hostname": "$(hostname)",
  "user": "$(whoami)",
  "release_checksum": "${RELEASE_CHECKSUM}",
  "ci_build_id": "${GITHUB_RUN_ID:-${GITLAB_CI_JOB_ID:-none}}",
  "build_at": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
}
EOF

# ------------------------------------------------------------------------------
# 8. Execução do Docker Compose
# ------------------------------------------------------------------------------
cd "${NEW_RELEASE_DIR}"

COMPOSE_EXEC=(docker compose --env-file "${SHARED_DIR}/.env" -f "${NEW_COMPOSE_PATH}")

if [ "${ENVIRONMENT}" = "prod" ]; then
    echo "🐳 [PROD] Baixando imagem remota (pull --quiet)..."
    "${COMPOSE_EXEC[@]}" pull --quiet
    echo "🚀 [PROD] Subindo containers..."
    "${COMPOSE_EXEC[@]}" up -d --wait --wait-timeout 120 --remove-orphans
else
    echo "🛠️ [HOM] Compilando e subindo imagem..."
    "${COMPOSE_EXEC[@]}" up -d --build --wait --wait-timeout 120 --remove-orphans
fi

# Captura do Image ID Hash para auditoria
IMAGE_ID_HASH=$(docker inspect --format='{{.Image}}' "${PROJECT_NAME}-app:${IMAGE_TAG}" 2>/dev/null || echo "unknown")
if [ -f "${NEW_RELEASE_DIR}/release.json" ] && command -v python3 >/dev/null 2>&1; then
    python3 - "${NEW_RELEASE_DIR}/release.json" "${IMAGE_ID_HASH}" << 'EOF' || true
import json, sys
try:
    json_file, img_hash = sys.argv[1], sys.argv[2]
    with open(json_file, 'r') as f:
        data = json.load(f)
    data['image_id'] = img_hash
    with open(json_file, 'w') as f:
        json.dump(data, f, indent=2)
except Exception:
    pass
EOF
fi

# ------------------------------------------------------------------------------
# 9. Validação de Saúde (HealthCheck & /up)
# ------------------------------------------------------------------------------
run_healthcheck "${NEW_RELEASE_DIR}"

# ------------------------------------------------------------------------------
# 10. Atualização Atômica do Symlink e Aprovação do Status
# ------------------------------------------------------------------------------
echo "🔗 Validação concluída! Atualizando symlink 'current'..."
ln -sfn "${NEW_RELEASE_DIR}" "${CURRENT_LINK}"

update_json_status "${NEW_RELEASE_DIR}/release.json" "SUCCESS"

# ------------------------------------------------------------------------------
# 11. Limpeza Segura de Releases Antigas
# ------------------------------------------------------------------------------
echo "🧹 Limpando releases antigas..."
if [ -d "${RELEASES_DIR}" ]; then
    ACTIVE_RELEASE=$(readlink -f "${CURRENT_LINK}")
    PROTECTED_SUCCESS_RELEASE=$(find_last_successful_release || echo "")

    find "${RELEASES_DIR}" -mindepth 1 -maxdepth 1 -type d | sort -r | tail -n +6 | while read -r old_release; do
        if [ "$(readlink -f "${old_release}")" != "${ACTIVE_RELEASE}" ] && [ "${old_release}" != "${PROTECTED_SUCCESS_RELEASE}" ]; then
            echo "  -> Removendo release antiga: ${old_release}"
            rm -rf "${old_release}"
        else
            echo "  -> Preservando release em uso/rollback: ${old_release}"
        fi
    done
fi

echo "✨ Deploy da release [${RELEASE_TAG}] concluído com sucesso!"