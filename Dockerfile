FROM php:8.4-fpm-alpine

# Instala dependências do sistema
RUN apk add --no-cache \
    postgresql-dev \
    libpq \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    nodejs \
    npm \
    redis \
    autoconf \
    build-base

# Instala extensões PHP
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

# Instala Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis \
    && apk del autoconf build-base

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www/blink

# Copia composer files primeiro para cache de dependências
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copia o restante do projeto
COPY . .

# Finaliza instalação do Composer
RUN composer dump-autoload --optimize

# Permissões para storage e bootstrap/cache
RUN chown -R www-data:www-data /var/www/blink/storage /var/www/blink/bootstrap/cache \
    && chmod -R 775 /var/www/blink/storage /var/www/blink/bootstrap/cache

# Expõe porta 8000 (php artisan serve)
EXPOSE 8000

USER www-data

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]