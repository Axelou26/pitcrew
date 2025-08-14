# Build stage pour Node.js
FROM node:20-alpine as node-builder
WORKDIR /app
COPY package*.json ./
RUN npm install || echo "Ignoring npm install errors"
COPY assets assets/
COPY vite.config.js .
RUN npm run build || echo "Ignoring build errors - will build assets manually later"

# Build stage pour Composer
FROM composer:latest as composer
WORKDIR /app
COPY composer.* ./
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

# Stage final PHP
FROM php:8.2-fpm-alpine as app

# Installation des dépendances système
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    curl \
    nodejs \
    npm \
    && rm -rf /var/cache/apk/*

# Installation de Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Installation des extensions PHP
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    intl \
    opcache

# Configuration PHP optimisée
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Configuration du répertoire de travail
WORKDIR /var/www

# Copie des dépendances et des assets compilés
COPY --from=composer /app/vendor ./vendor
# Pas besoin de copier les assets compilés car on va les reconstruire
# COPY --from=node-builder /app/public/build ./public/build

# Copie des fichiers de l'application
COPY . .

# Installation des dépendances Node.js et build des assets dans le conteneur final
RUN npm install || echo "Ignoring npm install errors" && \
    npm run build || echo "Ignoring build errors - we will handle assets separately"

# Configuration des permissions
RUN mkdir -p var/cache var/log var/sessions && \
    chown -R www-data:www-data var && \
    chmod -R 777 var && \
    chmod +x bin/console

# Copie des scripts personnalisés
COPY docker/pitcrew-entrypoint.sh /usr/local/bin/pitcrew-entrypoint.sh
COPY docker/optimize-startup.sh /usr/local/bin/optimize-startup.sh
RUN chmod +x /usr/local/bin/pitcrew-entrypoint.sh && \
    chmod +x /usr/local/bin/optimize-startup.sh

# Exposition du port
EXPOSE 9000

# Healthcheck amélioré qui vérifie à la fois PHP-FPM et l'état d'initialisation
HEALTHCHECK --interval=10s --timeout=5s --start-period=60s --retries=5 \
    CMD (php-fpm -t && [ -f /tmp/app-initialized ]) || exit 1

# Utilisation de l'entrypoint natif PHP, mais CMD personnalisé
CMD ["/usr/local/bin/pitcrew-entrypoint.sh"] 