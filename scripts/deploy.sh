#!/usr/bin/env bash

set -euo pipefail

# ==============================================================================
# SCRIPT DE DEPLOY & ROLLBACK (Laravel + Docker)
# Uso:
#   deploy.sh                 -> deploy hom
#   deploy.sh hom             -> deploy hom
#   deploy.sh staging         -> deploy hom
#   deploy.sh prod            -> deploy prod
#   deploy.sh production      -> deploy prod
#   deploy.sh dev             -> deploy dev
#   deploy.sh rollback        -> rollback hom
#   deploy.sh rollback prod   -> rollback prod
# ==============================================================================

ACTION="deploy"
ENV_INPUT="hom"

if [ "${1:-}" = "rollback" ]; then
    ACTION="rollback"
    ENV_INPUT="${2:-hom}"
else
    ENV_INPUT="${1:-hom}"
fi

# 1. Trava contra execuções concorrentes (Concurrency Lock)
LOCK_FILE="/tmp/blink-deploy-${ENV_INPUT}.lock"
exec 200>"${LOCK_FILE}"
if ! flock -n 200; then
    echo "❌ ERRO: Já existe uma operação em execução para [${ENV_INPUT}]."
    exit 1
fi

# 2. Mapeamento explícito de ambiente
case "${ENV_INPUT}" in
    prod|production|main)
        ENVIRONMENT="prod"
        PROJECT_NAME="blink-prod"
        COMPOSE_FILE="docker-compose.prod.yml"
        ;;
    hom|staging|stage)
        ENVIRONMENT="hom"
        PROJECT_NAME="blink-hom"
        COMPOSE_FILE="docker-compose.hom.yml"
        ;;
    dev|local)
        ENVIRONMENT="dev"
        PROJECT_NAME="blink-dev"
        COMPOSE_FILE="docker-compose.yml"
        ;;
    *)
        echo "❌ Ambiente inválido: '${ENV_INPUT}'"
        echo "Use: hom | staging | prod | dev"
        exit 1
        ;;
esac

# Identificação Git & Metadata
GIT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "nogit")
GIT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")
GIT_AUTHOR=$(git log -1 --format='%an' 2>/dev/null || echo "unknown")
RELEASE_TAG="$(date +%Y%m%d%H%M%S)-${GIT_COMMIT}"

# Configuração de Diretórios por Arquitetura
SOURCE_DIR="$(pwd)"

if [ "${ENVIRONMENT}" = "dev" ]; then
    BASE_DIR="/var/www/blk/blink-dev"
    SHARED_DIR="${BASE_DIR}"
    RELEASES_DIR=""
    CURRENT_LINK=""
    NEW_RELEASE_DIR="${BASE_DIR}"
    LOGS_DIR="${BASE_DIR}/storage/logs/deploy"
else
    BASE_DIR="/var/www/blk/${PROJECT_NAME}"
    RELEASES_DIR="${BASE_DIR}/releases"
    SHARED_DIR="${BASE_DIR}/shared"
    CURRENT_LINK="${BASE_DIR}/current"
    NEW_RELEASE_DIR="${RELEASES_DIR}/${RELEASE_TAG}"
    LOGS_DIR="${SHARED_DIR}/logs/deploy"
fi

# Redirecionamento de Logs
mkdir -p "${LOGS_DIR}"
DEPLOY_LOG_FILE="${LOGS_DIR}/deploy-$(date +%Y%m%d).log"

echo "======================================================================" | tee -a "${DEPLOY_LOG_FILE}"
echo "▶ Execute: ${0} ${ACTION} ${ENVIRONMENT} | Data: $(date -u +"%Y-%m-%dT%H:%M:%SZ")" | tee -a "${DEPLOY_LOG_FILE}"
echo "======================================================================" | tee -a "${DEPLOY_LOG_FILE}"

# Exportação Global Inicial
export PROJECT_NAME="${PROJECT_NAME}"
export IMAGE_TAG="${IMAGE_TAG:-${GIT_COMMIT}}"
export DOCKER_NETWORK="${DOCKER_NETWORK:-${PROJECT_NAME}-net}"
export SHARED_DIR="${SHARED_DIR}"
export RELEASE_PATH="${NEW_RELEASE_DIR}"
export CURRENT_LINK="${CURRENT_LINK}"

# Garantir que a rede compartilhada exista antes de qualquer operação
docker network create "${DOCKER_NETWORK}" 2>/dev/null || true

# ------------------------------------------------------------------------------
# HELPER: ATUALIZAÇÃO SEGURA DO JSON VIA PYTHON3
# ------------------------------------------------------------------------------
update_json_status() {
    local json_file="$1"
    local status_val="$2"

    if [ -z "${json_file}" ] || [ ! -f "${json_file}" ]; then return 0; fi

    if command -v python3 >/dev/null 2>&1; then
        python3 - "${json_file}" "${status_val}" << 'PYEOF' || true
import json, sys
try:
    json_file, status_val = sys.argv[1], sys.argv[2]
    with open(json_file, 'r') as f:
        data = json.load(f)
    data['status'] = status_val
    with open(json_file, 'w') as f:
        json.dump(data, f, indent=2)
except Exception:
    pass
PYEOF
    fi
}

# ------------------------------------------------------------------------------
# HELPER: DESCOBERTA DA ÚLTIMA RELEASE "SUCCESS"
# ------------------------------------------------------------------------------
find_last_successful_release() {
    if [ "${ENVIRONMENT}" = "dev" ]; then return 0; fi

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

# ==============================================================================
# 📍 COLOQUE O TRAP AQUI (Substituindo o antigo bloco de cleanup)
# ==============================================================================
cleanup_on_failure() {
    local exit_code=$?
    
    if [ $exit_code -ne 0 ] && [ "${ACTION}" = "deploy" ]; then
        echo "🚨 FALHA DETECTADA (Código ${exit_code}). Interrompendo..." | tee -a "${DEPLOY_LOG_FILE}"

        if [ "${ENVIRONMENT}" != "dev" ] && [ -d "${NEW_RELEASE_DIR}" ]; then
            update_json_status "${NEW_RELEASE_DIR}/release.json" "FAILED"

            local compose_file="${NEW_RELEASE_DIR}/${COMPOSE_FILE}"
            if [ -f "${compose_file}" ] && [ -f "${SHARED_DIR}/.env" ]; then
                echo "💾 Dumping logs de erro dos containers..." | tee -a "${DEPLOY_LOG_FILE}"
                
                # Opcional: Salva o log na pasta compartilhada persistente antes de apagar a release
                docker compose -p "${PROJECT_NAME}" --env-file "${SHARED_DIR}/.env" -f "${compose_file}" logs --timestamps --tail=200 > "${LOGS_DIR}/failed-${RELEASE_TAG}.log" 2>&1 || true
                
                docker compose -p "${PROJECT_NAME}" --env-file "${SHARED_DIR}/.env" -f "${compose_file}" down --remove-orphans 2>/dev/null || true
            fi

            if [ "$(readlink -f "${CURRENT_LINK}" 2>/dev/null)" = "${NEW_RELEASE_DIR}" ]; then
                echo "⚠️ Revertendo symlink para a última release estável..." | tee -a "${DEPLOY_LOG_FILE}"
                LAST_GOOD=$(find_last_successful_release || echo "")
                if [ -n "${LAST_GOOD}" ]; then
                    ln -sfn "${LAST_GOOD}" "${CURRENT_LINK}"
                fi
            fi

            echo "🧹 Removendo release abortada: ${NEW_RELEASE_DIR}" | tee -a "${DEPLOY_LOG_FILE}"
            rm -rf "${NEW_RELEASE_DIR}"
        fi
    fi
}
trap cleanup_on_failure EXIT

# ------------------------------------------------------------------------------
# FUNÇÃO REUTILIZÁVEL: HealthCheck
# ------------------------------------------------------------------------------
run_healthcheck() {
    local target_dir="$1"
    local compose_file="${target_dir}/${COMPOSE_FILE}"

    echo "⏳ Identificando ID do container da aplicação..." | tee -a "${DEPLOY_LOG_FILE}"
    APP_CID=$(docker compose -p "${PROJECT_NAME}" --env-file "${SHARED_DIR}/.env" -f "${compose_file}" ps -q app 2>/dev/null || true)

    if [ -z "${APP_CID}" ]; then
        echo "❌ ERRO CRÍTICO: Container 'app' não localizado." | tee -a "${DEPLOY_LOG_FILE}"
        return 1
    fi

    echo "⏳ Verificando integridade do container [${APP_CID:0:12}]..." | tee -a "${DEPLOY_LOG_FILE}"
    local MAX_WAIT=120
    local WAIT=0
    local HEALTHY=false

    while [ "$WAIT" -lt "$MAX_WAIT" ]; do
        STATE_STATUS=$(docker inspect --format='{{.State.Status}}' "${APP_CID}" 2>/dev/null || echo "exited")
        if [ "${STATE_STATUS}" != "running" ]; then
            echo "❌ ERRO CRÍTICO: Container não está rodando (Status: '${STATE_STATUS}')." | tee -a "${DEPLOY_LOG_FILE}"
            return 1
        fi

        HEALTH_STATUS=$(docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}no-healthcheck{{end}}' "${APP_CID}" 2>/dev/null || echo "unknown")

        case "$HEALTH_STATUS" in
            healthy|no-healthcheck)
                echo "✅ Container [${APP_CID:0:12}] está operacional!" | tee -a "${DEPLOY_LOG_FILE}"
                HEALTHY=true
                break
                ;;
            unhealthy)
                echo "❌ ERRO CRÍTICO: Container em estado UNHEALTHY!" | tee -a "${DEPLOY_LOG_FILE}"
                return 1
                ;;
            *)
                echo "  -> Status: running | Health: [${HEALTH_STATUS}]. Aguardando... (${WAIT}s/${MAX_WAIT}s)" | tee -a "${DEPLOY_LOG_FILE}"
                ;;
        esac

        sleep 2
        WAIT=$((WAIT+2))
    done

    if [ "$HEALTHY" = false ]; then
        echo "❌ TIMEOUT: HealthCheck excedeu ${MAX_WAIT}s." | tee -a "${DEPLOY_LOG_FILE}"
        return 1
    fi

    return 0
}

# ------------------------------------------------------------------------------
# RECURSO: ROLLBACK
# ------------------------------------------------------------------------------
if [ "${ACTION}" = "rollback" ]; then
    if [ "${ENVIRONMENT}" = "dev" ]; then
        echo "❌ ROLLBACK INDISPONÍVEL: O ambiente 'dev' não utiliza controle de releases." | tee -a "${DEPLOY_LOG_FILE}"
        exit 1
    fi

    echo "⏪ Iniciando Rollback no ambiente: [${ENVIRONMENT}]..." | tee -a "${DEPLOY_LOG_FILE}"

    ACTIVE_RELEASE=$(readlink -f "${CURRENT_LINK}" 2>/dev/null || echo "")
    TARGET_ROLLBACK_DIR=$(find_last_successful_release || echo "")

    if [ -z "${TARGET_ROLLBACK_DIR}" ] || [ ! -d "${TARGET_ROLLBACK_DIR}" ]; then
        echo "❌ ERRO FATAL: Nenhuma release estável anterior encontrada." | tee -a "${DEPLOY_LOG_FILE}"
        exit 1
    fi

    echo "🎯 Restaurando release: $(basename "${TARGET_ROLLBACK_DIR}")" | tee -a "${DEPLOY_LOG_FILE}"
    TARGET_COMPOSE="${TARGET_ROLLBACK_DIR}/${COMPOSE_FILE}"

    if [ -d "${ACTIVE_RELEASE}" ]; then
        ACTIVE_COMPOSE="${ACTIVE_RELEASE}/${COMPOSE_FILE}"
        if [ -f "${ACTIVE_COMPOSE}" ]; then
            docker compose -p "${PROJECT_NAME}" --env-file "${SHARED_DIR}/.env" -f "${ACTIVE_COMPOSE}" down --remove-orphans 2>/dev/null || true
        fi
    fi

    export RELEASE_PATH="${TARGET_ROLLBACK_DIR}"

    ln -sfn "${TARGET_ROLLBACK_DIR}" "${CURRENT_LINK}"
    docker compose -p "${PROJECT_NAME}" --env-file "${SHARED_DIR}/.env" -f "${TARGET_COMPOSE}" up -d --wait --wait-timeout 120 --remove-orphans

    if run_healthcheck "${TARGET_ROLLBACK_DIR}"; then
        echo "✨ Rollback concluído com sucesso!" | tee -a "${DEPLOY_LOG_FILE}"
        exit 0
    else
        echo "❌ ERRO CRÍTICO: Rollback falhou no HealthCheck!" | tee -a "${DEPLOY_LOG_FILE}"
        exit 1
    fi
fi

# ------------------------------------------------------------------------------
# FLUXO DE DEPLOY
# ------------------------------------------------------------------------------
echo "🚀 Iniciando deploy no ambiente: [${ENVIRONMENT}] (${PROJECT_NAME})" | tee -a "${DEPLOY_LOG_FILE}"

FREE_DISK_MB=$(df -Pm "${BASE_DIR}" 2>/dev/null | awk 'NR==2 {print $4}' || echo "0")
if [ "${FREE_DISK_MB}" -lt 1024 ]; then
    echo "❌ ABORTANDO: Espaço em disco insuficiente (${FREE_DISK_MB} MB livres)." | tee -a "${DEPLOY_LOG_FILE}"
    exit 1
fi

if [ ! -f "${SHARED_DIR}/.env" ]; then
    echo "❌ ABORTANDO: Arquivo de ambiente '${SHARED_DIR}/.env' não encontrado." | tee -a "${DEPLOY_LOG_FILE}"
    echo "Crie o arquivo em: ${SHARED_DIR}/.env" | tee -a "${DEPLOY_LOG_FILE}"
    exit 1
fi

# ------------------------------------------------------------------------------
# DEPLOY AMBIENTE DEV
# ------------------------------------------------------------------------------
if [ "${ENVIRONMENT}" = "dev" ]; then
    echo "🛠️ [DEV] Executando Docker Compose na raiz do projeto..." | tee -a "${DEPLOY_LOG_FILE}"
    
    DEV_COMPOSE_PATH="${BASE_DIR}/${COMPOSE_FILE}"
    if [ ! -f "${DEV_COMPOSE_PATH}" ]; then
        echo "❌ ERRO: Arquivo '${COMPOSE_FILE}' não encontrado em ${BASE_DIR}." | tee -a "${DEPLOY_LOG_FILE}"
        exit 1
    fi

    docker compose -p "${PROJECT_NAME}" --env-file "${SHARED_DIR}/.env" -f "${DEV_COMPOSE_PATH}" config -q
    docker compose -p "${PROJECT_NAME}" --env-file "${SHARED_DIR}/.env" -f "${DEV_COMPOSE_PATH}" up -d --build --remove-orphans

    run_healthcheck "${BASE_DIR}"
    echo "✨ Build do ambiente DEV concluído com sucesso!" | tee -a "${DEPLOY_LOG_FILE}"
    exit 0
fi

# ------------------------------------------------------------------------------
# DEPLOY AMBIENTES HOM E PROD (Atômico via Releases)
# ------------------------------------------------------------------------------
mkdir -p "${RELEASES_DIR}"
mkdir -p "${SHARED_DIR}"

echo "📋 Sincronizando arquivos para a release [${RELEASE_TAG}]..." | tee -a "${DEPLOY_LOG_FILE}"
mkdir -p "${NEW_RELEASE_DIR}"

# Sincroniza arquivos do projeto excluindo dependências pesadas e temporárias
rsync -avq --exclude='.git' \
          --exclude='node_modules' \
          --exclude='vendor' \
          --exclude='storage/logs/*' \
          --exclude='storage/framework/cache/*' \
          --exclude='storage/framework/sessions/*' \
          --exclude='storage/framework/views/*' \
          "${SOURCE_DIR}/" "${NEW_RELEASE_DIR}/"

NEW_COMPOSE_PATH="${NEW_RELEASE_DIR}/${COMPOSE_FILE}"
if [ ! -f "${NEW_COMPOSE_PATH}" ]; then
    echo "❌ ERRO: Arquivo '${COMPOSE_FILE}' não encontrado na nova release." | tee -a "${DEPLOY_LOG_FILE}"
    exit 1
fi

# Re-exporta variáveis para garantir alcance global no compose
export PROJECT_NAME="${PROJECT_NAME}"
export IMAGE_TAG="${IMAGE_TAG:-${GIT_COMMIT}}"
export DOCKER_NETWORK="${DOCKER_NETWORK:-${PROJECT_NAME}-net}"
export SHARED_DIR="${SHARED_DIR}"
export RELEASE_PATH="${NEW_RELEASE_DIR}"
export CURRENT_LINK="${CURRENT_LINK}"

# Validação do arquivo compose
docker compose -p "${PROJECT_NAME}" --env-file "${SHARED_DIR}/.env" -f "${NEW_COMPOSE_PATH}" config -q

cat <<EOF > "${NEW_RELEASE_DIR}/release.json"
{
  "release": "${RELEASE_TAG}",
  "status": "PENDING",
  "image_tag": "${IMAGE_TAG}",
  "git_commit": "${GIT_COMMIT}",
  "branch": "${GIT_BRANCH}",
  "author": "${GIT_AUTHOR}",
  "environment": "${ENVIRONMENT}",
  "hostname": "$(hostname)",
  "build_at": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
}
EOF

cd "${NEW_RELEASE_DIR}"
COMPOSE_EXEC=(docker compose -p "${PROJECT_NAME}" --env-file "${SHARED_DIR}/.env" -f "${NEW_COMPOSE_PATH}")

if [ "${ENVIRONMENT}" = "prod" ]; then
    echo "🐳 [PROD] Compilando e subindo containers..." | tee -a "${DEPLOY_LOG_FILE}"
    "${COMPOSE_EXEC[@]}" build
    "${COMPOSE_EXEC[@]}" up -d --wait --wait-timeout 120 --remove-orphans
else
    echo "🐳 Compilando e subindo containers para [${ENVIRONMENT^^}]..." | tee -a "${DEPLOY_LOG_FILE}"
    "${COMPOSE_EXEC[@]}" build
    "${COMPOSE_EXEC[@]}" up -d --wait --wait-timeout 120 --remove-orphans
fi

run_healthcheck "${NEW_RELEASE_DIR}"

echo "🔗 Atualizando symlink 'current'..." | tee -a "${DEPLOY_LOG_FILE}"
ln -sfn "${NEW_RELEASE_DIR}" "${CURRENT_LINK}"

update_json_status "${NEW_RELEASE_DIR}/release.json" "SUCCESS"

echo "🧹 Limpando releases antigas..." | tee -a "${DEPLOY_LOG_FILE}"
if [ -d "${RELEASES_DIR}" ]; then
    ACTIVE_RELEASE=$(readlink -f "${CURRENT_LINK}" 2>/dev/null || echo "")
    PROTECTED_SUCCESS_RELEASE=$(find_last_successful_release || echo "")

    find "${RELEASES_DIR}" -mindepth 1 -maxdepth 1 -type d | sort -r | tail -n +6 | while read -r old_release; do
        if [ "$(readlink -f "${old_release}" 2>/dev/null)" != "${ACTIVE_RELEASE}" ] && [ "${old_release}" != "${PROTECTED_SUCCESS_RELEASE}" ]; then
            echo "  -> Removendo: ${old_release}" | tee -a "${DEPLOY_LOG_FILE}"
            rm -rf "${old_release}"
        fi
    done
fi

echo -e "\033[32m✨ Deploy da release [${RELEASE_TAG}] em ${ENVIRONMENT^^} concluído com sucesso!\033[0m" | tee -a "${DEPLOY_LOG_FILE}"