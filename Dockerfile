# ==========================================
# STAGE 1: Composer (Dependências PHP)
# ==========================================
FROM composer:2 AS vendor

WORKDIR /app

# Copia arquivos de definição e estrutura de código necessária para pacotes com autoloader
COPY composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-interaction \
    --no-scripts

# ==========================================
# STAGE 2: Node.js (Build do Frontend/Vite)
# ==========================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --include=dev --legacy-peer-deps

COPY . .

ENV NODE_ENV=production
RUN npm run build

# ==========================================
# STAGE 3: Imagem Final de Produção
# ==========================================
FROM php:8.4-fpm-alpine

# Instalação apenas das dependências de runtime do SO, Nginx e utilitários de rede (netcat)
RUN apk add --no-cache \
    bash \
    curl \
    netcat-openbsd \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    postgresql-client \
    nginx

# Diretórios base do Nginx
RUN mkdir -p /run/nginx

# Instalação das extensões PHP de produção
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions pdo_pgsql pgsql gd bcmath intl mbstring opcache redis

WORKDIR /var/www

# Copia a aplicação inteira
COPY . /var/www

# Copia a pasta vendor pronta (Stage 1)
COPY --from=vendor /app/vendor /var/www/vendor

# Copia os assets compilados do Vite/Public (Stage 2)
COPY --from=frontend /app/public/build /var/www/public/build

# Configurações do PHP e Nginx direto na imagem
COPY docker/php/php.ini /usr/local/etc/php/conf.d/blink.ini
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Estrutura de diretórios de runtime e permissões para o www-data
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/app/public \
    storage/logs \
    bootstrap/cache \
 && chown -R www-data:www-data /var/www

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Healthcheck HTTP usando a rota /up do Laravel
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD php artisan about --no-ansi > /dev/null || exit 1

EXPOSE 80 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]