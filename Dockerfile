FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    icu-dev \
    oniguruma-dev \
    postgresql-client

# Copia o instalador oficial de extensões PHP prontas
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

# Instala todas as extensões e o Redis em segundos
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    gd \
    bcmath \
    intl \
    mbstring \
    opcache \
    redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copia o entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]