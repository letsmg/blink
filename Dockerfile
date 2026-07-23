# Usa uma imagem oficial do PHP FPM limpa
FROM php:8.4-fpm-alpine

# Instala as dependências de sistema e pacotes -dev necessários para compilar as extensões PHP
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

# Instala extensões PHP pré-compiladas do core
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

# Instala a extensão Redis via PECL de forma otimizada (removendo lixos em seguida)
RUN apk add --no-cache --virtual .build-deps autoconf build-base \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho padrão
WORKDIR /var/www/blink

# Copia todo o projeto já pronto que veio do GitHub Actions
COPY . .

# Cria explicitamente as pastas usando caminhos absolutos e ajusta permissões
RUN mkdir -p /var/www/blink/storage \
    && mkdir -p /var/www/blink/bootstrap/cache \
    && chown -R www-data:www-data /var/www/blink \
    && chmod -R 775 /var/www/blink/storage \
    && chmod -R 775 /var/www/blink/bootstrap/cache

# Expõe porta 8000
EXPOSE 8000

# A partir daqui o container roda com o usuário sem privilégios por segurança
USER www-data

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]