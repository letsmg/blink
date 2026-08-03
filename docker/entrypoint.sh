#!/bin/bash
set -e

echo "=== Blink Entrypoint ==="

# ------------------------------------------------------------
# 1. VALIDAÇÃO DE MONTAGEM E DIRETÓRIO DE TRABALHO
# ------------------------------------------------------------
cd /var/www

if [ ! -d "resources/views" ]; then
    echo "❌ ERRO CRÍTICO: Pasta 'resources/views' não encontrada em $(pwd)."
    echo "Conteúdo atual detectado dentro do container:"
    ls -la
    exit 1
fi

# Garante apenas as pastas de runtime do storage
mkdir -p /var/www/storage/framework/{cache/data,sessions,views}
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null 
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null 

# ------------------------------------------------------------
# 2. AGUARDAR BANCO DE DADOS E VALIDAR CREDENCIAIS
# ------------------------------------------------------------
if [ -n "$DB_HOST" ]; then
    DB_PORT="${DB_PORT:-5432}"
    echo "⏳ Aguardando serviço do PostgreSQL em ${DB_HOST}:${DB_PORT}..."

    # Passo A: Espera o serviço do Postgres estar pronto para conexões (sem pedir senha)
    MAX_TRIES=30
    TRIES=0
    until pg_isready -h "$DB_HOST" -p "$DB_PORT" -q || [ $TRIES -eq $MAX_TRIES ]; do
        echo "   PostgreSQL ainda não está aceitando conexões... aguardando"
        sleep 1
        TRIES=$((TRIES + 1))
    done

    if [ $TRIES -eq $MAX_TRIES ]; then
        echo "❌ ERRO: O container do PostgreSQL não respondeu na porta $DB_PORT em 30 segundos!"
        exit 1
    fi

    # Passo B: Testa autenticação real SEM esconder o erro com '2>/dev/null'
    echo "🔑 Testando credenciais do banco '$DB_DATABASE'..."
    if ! PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c '\q'; then
        echo ""
        echo "❌ ERRO FATAL DE AUTENTICAÇÃO COM O BANCO DE DADOS!"
        echo "   Verifique se DB_USERNAME, DB_PASSWORD e DB_DATABASE no .env de produção estão corretos."
        exit 1
    fi

    echo "✅ Conexão com o PostgreSQL estabelecida com sucesso!"
fi

# ------------------------------------------------------------
# 3. ROTINAS DE CONFIGURAÇÃO DO LARAVEL
# ------------------------------------------------------------
echo "Running Laravel setup..."

# Link do storage caso não exista
php artisan storage:link --force --no-interaction 2>/dev/null

# Limpa TODOS os caches de disco (config, rotas, views, eventos)
# NÃO PODE USAR O OPTIMIZE AQUI QUE CA ERRO AO RUNTIME EM PRODUÇÃO, POIS ELE CRIA CACHE DE CONFIGURAÇÃO E ROTAS QUE PODEM FICAR DESATUALIZADOS
#php artisan optimize:clear

#SEMPRE USAR ESSES 4 ABAIXO SEPARADOS
# Limpeza de cache segura
php artisan config:clear 
php artisan route:clear 
php artisan view:clear 
#php artisan event:clear

echo "Entrypoint complete. Starting $@"
exec "$@"