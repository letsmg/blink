FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libpq \
    libpng \
    libpng-dev \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    freetype \
    freetype-dev \
    zlib-dev \
    oniguruma \
    oniguruma-dev \
    libxml2 \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        xml

RUN apk add --no-cache --virtual .build-deps autoconf build-base \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 1. Cria a estrutura de pastas ANTES de qualquer cópia
RUN mkdir -p /var/www/blink/storage/framework/sessions \
    /var/www/blink/storage/framework/views \
    /var/www/blink/storage/framework/cache \
    /var/www/blink/bootstrap/cache

# Define o diretório de trabalho primeiro
WORKDIR /var/www/blink

# Cria a estrutura de diretórios necessária para o Laravel
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    bootstrap/cache

# Copia os arquivos do projeto para o diretório atual
COPY . /var/www/blink

# Garante as permissões adequadas para o usuário do webserver
RUN chown -R www-data:www-data /var/www/blink \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

USER www-data

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]