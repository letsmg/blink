#!/bin/sh
set -eu

echo "=================================="
echo " Blink Entrypoint"
echo "=================================="

cd /var/www

#
# ------------------------------------------------------------------
# 1. Validação de integridade do código
# ------------------------------------------------------------------
#
if [ ! -f artisan ]; then
    echo "[ERROR] arquivo 'artisan' não encontrado na raiz."
    exit 1
fi

if [ ! -d resources/views ]; then
    echo "[ERROR] pasta 'resources/views' não encontrada."
    exit 1
fi

#
# ------------------------------------------------------------------
# 2. Diretórios de runtime e permissões
# ------------------------------------------------------------------
#
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

#
# ------------------------------------------------------------------
# 3. Rotinas de inicialização do PHP-FPM
# ------------------------------------------------------------------
#
if [ "${1:-}" = "php-fpm" ]; then

    echo ""
    echo "[INFO] PHP-FPM Container detectado."
    php artisan --version

    #
    # Validar presença de variáveis essenciais no ambiente
    #
    MISSING_VARS=""
    
    [ -z "${APP_KEY:-}" ]       && MISSING_VARS="$MISSING_VARS APP_KEY"
    [ -z "${DB_CONNECTION:-}" ] && MISSING_VARS="$MISSING_VARS DB_CONNECTION"
    [ -z "${DB_HOST:-}" ]       && MISSING_VARS="$MISSING_VARS DB_HOST"
    [ -z "${DB_DATABASE:-}" ]   && MISSING_VARS="$MISSING_VARS DB_DATABASE"
    [ -z "${DB_USERNAME:-}" ]   && MISSING_VARS="$MISSING_VARS DB_USERNAME"

    if [ -n "$MISSING_VARS" ]; then
        echo "[ERROR] Variáveis de ambiente obrigatórias ausentes:$MISSING_VARS"
        exit 1
    fi

    #
    # Aguardar PostgreSQL estar online e autenticar
    #
    DB_HOST_VAL="${DB_HOST:-}"
    if [ -n "$DB_HOST_VAL" ]; then
        DB_PORT_VAL="${DB_PORT:-5432}"

        echo ""
        echo "[INFO] Aguardando PostgreSQL em $DB_HOST_VAL:$DB_PORT_VAL..."

        MAX_TRIES=30
        TRIES=0

        until pg_isready -h "$DB_HOST_VAL" -p "$DB_PORT_VAL" -q || [ "$TRIES" -eq "$MAX_TRIES" ]
        do
            TRIES=$((TRIES+1))
            sleep 1
        done

        if [ "$TRIES" -eq "$MAX_TRIES" ]; then
            echo "[ERROR] PostgreSQL indisponível após $MAX_TRIES tentativas."
            exit 1
        fi

        echo "[INFO] PostgreSQL disponível."
        echo "[INFO] Testando autenticação no PostgreSQL..."

        PGPASSWORD="${DB_PASSWORD:-}" \
        psql \
            -h "$DB_HOST_VAL" \
            -p "$DB_PORT_VAL" \
            -U "$DB_USERNAME" \
            -d "$DB_DATABASE" \
            -c '\q'

        echo "[INFO] Autenticação no banco validada com sucesso."
    fi

    #
    # Aguardar Redis estar online (se configurado)
    #
    REDIS_HOST_VAL="${REDIS_HOST:-}"
    if [ -n "$REDIS_HOST_VAL" ]; then
        echo ""
        echo "[INFO] Aguardando Redis em $REDIS_HOST_VAL..."

        REDIS_PORT_VAL="${REDIS_PORT:-6379}"
        
        until nc -z "$REDIS_HOST_VAL" "$REDIS_PORT_VAL" >/dev/null 2>&1
        do
            sleep 1
        done

        echo "[INFO] Redis disponível."
    fi

    #
    # Storage Link (Simplificado)
    #
    php artisan storage:link --force --no-interaction || true

    #
    # Migrações opcionais (evita race conditions em múltiplos containers)
    #
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        echo ""
        echo "[INFO] Executando migrações do banco de dados..."
        php artisan migrate --force --no-interaction
    else
        echo ""
        echo "[INFO] Pulando migrações (RUN_MIGRATIONS != true)."
    fi

    #
    # Otimizações e limpeza completa de Caches
    #
    echo ""
    echo "[INFO] Limpando caches antigos com optimize:clear..."
    php artisan optimize:clear

    echo "[INFO] Gerando novos caches otimizados..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    #
    # Descoberta de pacotes
    #
    php artisan package:discover --ansi

    echo ""
    echo "[INFO] Laravel inicializado e pronto para tráfego."
fi

#
# ------------------------------------------------------------------
# 4. Iniciar o processo principal
# ------------------------------------------------------------------
#
echo ""
echo "[INFO] Executando processo principal: $*"

exec "$@"