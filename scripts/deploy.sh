#!/bin/bash
set -e

# ============================================================
# 🚀 Blink - Script Principal de Deploy (Fonte Única)
# ============================================================
# Uso: bash scripts/deploy.sh [staging|production]
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

ENVIRONMENT=${1:-staging}
PROJECT_ROOT="$(pwd)"

# Define variáveis por ambiente
if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ] || [ "$ENVIRONMENT" = "main" ]; then
  ENV_LABEL="prod"
  TARGET_ROOT="/var/www/blk/blink-prod"
  PROJECT_NAME="blink-prod"
  DOCKER_NETWORK="blink-prod-net"
  VOLUME_PGSQL="blink-prod-pgsql-data"
  VOLUME_REDIS="blink-prod-redis-data"
  SRC_COMPOSE="docker-compose.prod.yml"
else
  ENV_LABEL="hom"
  TARGET_ROOT="/var/www/blk/blink-hom"
  PROJECT_NAME="blink-hom"
  DOCKER_NETWORK="blink-hom-net"
  VOLUME_PGSQL="blink-hom-pgsql-data"
  VOLUME_REDIS="blink-hom-redis-data"
  SRC_COMPOSE="docker-compose.hom.yml"
fi

# Padroniza a cópia e o nome do arquivo de composição Docker
DOCKER_COMPOSE="docker-compose.yml"
if [ -f "${PROJECT_ROOT}/${SRC_COMPOSE}" ]; then
  echo "📄 Copiando arquivo de composição (${SRC_COMPOSE} -> ${DOCKER_COMPOSE})..."
  cp "${PROJECT_ROOT}/${SRC_COMPOSE}" "${PROJECT_ROOT}/${DOCKER_COMPOSE}"
fi

SHARED_PATH="${TARGET_ROOT}/shared"
RELEASES_PATH="${TARGET_ROOT}/releases"
CURRENT_LINK="${TARGET_ROOT}/current"

# ⚠️ Exporta variáveis para o Docker Compose ler
export CURRENT_LINK DOCKER_NETWORK VOLUME_PGSQL VOLUME_REDIS

echo -e "${BLUE}====================================================${NC}"
echo -e "${BLUE} 🚀 INICIANDO DEPLOY - AMBIENTE: ${ENV_LABEL^^} ${NC}"
echo -e "${BLUE}====================================================${NC}"
echo "📂 Target Root:   $TARGET_ROOT"
echo "🐳 Compose File:  $DOCKER_COMPOSE"
echo "📦 Project Name:  $PROJECT_NAME"
echo ""

# ------------------------------------------------------------
# 1. ESTRUTURA DE DIRETÓRIOS E ENV
# ------------------------------------------------------------
echo "📁 Preparando diretórios compartilhados..."
sudo mkdir -p "${RELEASES_PATH}"
sudo mkdir -p "${SHARED_PATH}/storage"/{app/public,framework/{cache/data,sessions,views},logs}

# Cria o .env compartilhado APENAS na primeira execução (se não existir)
if [ ! -f "${SHARED_PATH}/.env" ]; then
  echo "⚙️ Criando arquivo .env compartilhado inicial..."
  if [ "$ENV_LABEL" = "prod" ] && [ -f "${PROJECT_ROOT}/.env.prod" ]; then
    sudo cp "${PROJECT_ROOT}/.env.prod" "${SHARED_PATH}/.env"
  elif [ -f "${PROJECT_ROOT}/.env.${ENV_LABEL}" ]; then
    sudo cp "${PROJECT_ROOT}/.env.${ENV_LABEL}" "${SHARED_PATH}/.env"
  elif [ -f "${PROJECT_ROOT}/.env.example" ]; then
    sudo cp "${PROJECT_ROOT}/.env.example" "${SHARED_PATH}/.env"
  else
    echo "APP_NAME=Blink" | sudo tee "${SHARED_PATH}/.env" > /dev/null
  fi
fi

# Corrige o proprietário de toda a estrutura para o seu usuário de deploy
echo "🔑 Ajustando proprietários e permissões em ${TARGET_ROOT}..."
sudo chown -R $USER:www-data "${TARGET_ROOT}"

# Ajusta permissões nos arquivos e pastas compartilhados
chmod -R 775 "${SHARED_PATH}"
chmod 664 "${SHARED_PATH}/.env"

# ------------------------------------------------------------
# 2. DEFINIÇÃO DA RELEASE & CÓPIA DO CÓDIGO-FONTE
# ------------------------------------------------------------
NOW_DATE="$(date +%Y-%m-%d_%H-%M)"

# Calcula a sequência da release (ex: 001, 002...)
LAST_SEQ=$(ls -1 "${RELEASES_PATH}" 2>/dev/null | grep -oE '[0-9]+$' | sort -n | tail -n 1 || true)
if [ -z "$LAST_SEQ" ]; then
  NEXT_SEQ=1
else
  NEXT_SEQ=$((10#$LAST_SEQ + 1))
fi

SEQ_FORMATTED=$(printf "%03d" $NEXT_SEQ)
RELEASE_NAME="${NOW_DATE}-${SEQ_FORMATTED}"
NEW_RELEASE="${RELEASES_PATH}/${RELEASE_NAME}"

echo "📦 Criando Release #${NEXT_SEQ}: ${RELEASE_NAME}"
mkdir -p "${NEW_RELEASE}"

echo "📋 Copiando código-fonte para a release..."
rsync -av \
  --exclude '.git' \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude '.github' \
  --exclude 'tests' \
  --exclude '.env*' \
  --exclude 'storage' \
  --exclude 'scripts' \
  "${PROJECT_ROOT}/" "${NEW_RELEASE}/"

cd "${NEW_RELEASE}"

# ------------------------------------------------------------
# 3. SYMLINKS E PERMISSÕES NA NOVA RELEASE
# ------------------------------------------------------------
echo "🔗 Criando symlinks para a pasta shared..."
mkdir -p "${NEW_RELEASE}/public"

if [ -d "${NEW_RELEASE}/storage/app/public" ]; then
  cp -R "${NEW_RELEASE}/storage/app/public/." "${SHARED_PATH}/storage/app/public/" 2>/dev/null || true
fi

rm -rf "${NEW_RELEASE}/storage" "${NEW_RELEASE}/public/storage" "${NEW_RELEASE}/.env"
ln -sfn "${SHARED_PATH}/storage" "${NEW_RELEASE}/storage"
ln -sfn "${SHARED_PATH}/storage/app/public" "${NEW_RELEASE}/public/storage"
ln -sfn "${SHARED_PATH}/.env" "${NEW_RELEASE}/.env"

chmod -R 775 "${NEW_RELEASE}/bootstrap/cache" 2>/dev/null || true

# 🛡️ TRAVA DE SEGURANÇA DOCKER: Garante que o symlink 'current' exista antes do Compose subir
if [ ! -L "${CURRENT_LINK}" ] && [ ! -d "${CURRENT_LINK}" ]; then
  echo "🔗 Criando symlink 'current' inicial..."
  ln -sfn "${NEW_RELEASE}" "${CURRENT_LINK}"
fi

# ------------------------------------------------------------
# 4. DEPENDÊNCIAS (COMPOSER & NPM)
# ------------------------------------------------------------
echo "📚 Instalando dependências na nova release..."
if [ -f "composer.json" ]; then
  composer install --no-dev --optimize-autoloader --no-interaction
fi

if [ -f "package.json" ]; then
  NODE_ENV=development npm install --include=dev --legacy-peer-deps
  npm run build
fi

# ------------------------------------------------------------
# 5. ATUALIZAÇÃO ATÔMICA & DOCKER
# ------------------------------------------------------------
echo "🐳 Garantindo infraestrutura Docker..."
docker volume create "${VOLUME_PGSQL}" 2>/dev/null || true
docker volume create "${VOLUME_REDIS}" 2>/dev/null || true

# Aponta o symlink 'current' para a nova release
echo "🔄 Atualizando symlink 'current' para a nova release..."
ln -sfn "${NEW_RELEASE}" "${CURRENT_LINK}"

docker compose -f "${CURRENT_LINK}/${DOCKER_COMPOSE}" -p "${PROJECT_NAME}" --env-file "${SHARED_PATH}/.env" up -d --build --remove-orphans

echo "⏳ Aguardando o container 'app' estabilizar..."
MAX_RETRIES=15
RETRY_COUNT=0
UNTIL_RUNNING=false

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
  CONTAINER_STATUS=$(docker inspect --format='{{.State.Status}}' "${PROJECT_NAME}-app" 2>/dev/null || echo "not_found")
  if [ "$CONTAINER_STATUS" = "running" ]; then
    UNTIL_RUNNING=true
    break
  fi
  echo "  - Status atual do app: '$CONTAINER_STATUS'. Aguardando..."
  sleep 2
  RETRY_COUNT=$((RETRY_COUNT + 1))
done

if [ "$UNTIL_RUNNING" = false ]; then
  echo -e "${RED}❌ O container 'app' não estabilizou (Status: $CONTAINER_STATUS). Verifique com 'docker logs ${PROJECT_NAME}-app'${NC}"
  exit 1
fi

# ------------------------------------------------------------
# 5.1 PERMISSÕES E ESTRUTURA INTERNA (Executa ANTES do Artisan)
# ------------------------------------------------------------
echo "🔑 Ajustando estrutura de diretórios e permissões do Laravel..."

# 1. Cria todas as subpastas do framework no host e garante permissão 777 no shared e no current
sudo mkdir -p "${SHARED_PATH}/storage"/{app/public,framework/{cache/data,sessions,views},logs}
sudo chmod -R 777 "${SHARED_PATH}/storage"
sudo chmod -R 777 "${CURRENT_LINK}/bootstrap/cache"

# 2. Garante a criação e permissão internamente no container (sem usar -it)
docker compose -f "${CURRENT_LINK}/${DOCKER_COMPOSE}" -p "${PROJECT_NAME}" --env-file "${SHARED_PATH}/.env" exec -T app mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs
docker compose -f "${CURRENT_LINK}/${DOCKER_COMPOSE}" -p "${PROJECT_NAME}" --env-file "${SHARED_PATH}/.env" exec -T app chmod -R 777 storage bootstrap/cache

# ------------------------------------------------------------
# 6. MIGRATIONS & OTIMIZAÇÕES ARTISAN
# ------------------------------------------------------------
echo "⚡ Executando rotinas finais do Laravel..."

# 1º PRIMEIRO: Executa as migrations para criar as tabelas do banco (incluindo a tabela 'cache')
docker compose -f "${CURRENT_LINK}/${DOCKER_COMPOSE}" -p "${PROJECT_NAME}" --env-file "${SHARED_PATH}/.env" exec -T app php artisan migrate --force

# 2º SEGUNDO: Se for ambiente de homologação, executa as seeds
if [ "$ENV_LABEL" = "hom" ]; then
  docker compose -f "${CURRENT_LINK}/${DOCKER_COMPOSE}" -p "${PROJECT_NAME}" --env-file "${SHARED_PATH}/.env" exec -T app php artisan db:seed --force || true
fi

# 3º TERCEIRO: Agora que o banco está pronto, limpa e gera as otimizações de cache
docker compose -f "${CURRENT_LINK}/${DOCKER_COMPOSE}" -p "${PROJECT_NAME}" --env-file "${SHARED_PATH}/.env" exec -T app php artisan optimize:clear
docker compose -f "${CURRENT_LINK}/${DOCKER_COMPOSE}" -p "${PROJECT_NAME}" --env-file "${SHARED_PATH}/.env" exec -T app php artisan optimize

# ------------------------------------------------------------
# 7. ROTATIVIDADE (Manter últimas 5 releases)
# ------------------------------------------------------------
echo "🧹 Limpando releases antigas (mantendo as 5 mais recentes)..."
cd "${RELEASES_PATH}"
ls -1dt */ 2>/dev/null | tail -n +6 | xargs -I {} rm -rf "{}" 2>/dev/null || true

# ------------------------------------------------------------
# 8. HEALTH CHECK
# ------------------------------------------------------------
DETECTED_PORT=$(docker compose -f "${CURRENT_LINK}/${DOCKER_COMPOSE}" -p "${PROJECT_NAME}" port nginx 80 2>/dev/null | awk -F':' '{print $NF}' || true)
if [ -z "$DETECTED_PORT" ]; then
  DETECTED_PORT=$(docker compose -f "${CURRENT_LINK}/${DOCKER_COMPOSE}" -p "${PROJECT_NAME}" port web 80 2>/dev/null | awk -F':' '{print $NF}' || true)
fi
APP_PORT=${DETECTED_PORT:-80}

echo "🩺 Verificando resposta da aplicação na porta ${APP_PORT}..."
SUCCESS=false
for i in {1..5}; do
  HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${APP_PORT}/" || true)
  if [[ "$HTTP_STATUS" =~ ^(200|301|302|401)$ ]]; then
    echo -e "${GREEN}✅ Aplicação respondendo! (HTTP $HTTP_STATUS)${NC}"
    SUCCESS=true
    break
  fi
  sleep 2
done

if [ "$SUCCESS" = false ]; then
  echo -e "${RED}❌ Health check falhou na porta ${APP_PORT}${NC}"
  exit 1
fi

echo -e "${GREEN}🎉 DEPLOY CONCLUÍDO COM SUCESSO! Release: ${RELEASE_NAME}${NC}"