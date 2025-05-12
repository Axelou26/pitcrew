# Build stage
FROM node:20-alpine as node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY assets assets/
COPY webpack.config.js .
RUN npm run build

FROM composer:latest as composer
WORKDIR /app
COPY composer.* ./
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader

FROM php:8.2-fpm as app

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-install pdo pdo_mysql zip intl opcache

# Configuration PHP
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Configuration du répertoire de travail
WORKDIR /var/www

# Copie des dépendances et des assets compilés
COPY --from=composer /app/vendor ./vendor
COPY --from=node-builder /app/public/build ./public/build

# Copie des fichiers de l'application
COPY . .

# Configuration des permissions
RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data var && \
    chmod -R 777 var && \
    chmod +x bin/console

# Exposition du port
EXPOSE 9000

CMD ["php-fpm"] 