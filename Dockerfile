FROM php:8.2-fpm

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-install pdo pdo_mysql zip intl opcache

# Configuration PHP
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Création des répertoires et fichiers de log
RUN mkdir -p /var/log && \
    touch /var/log/php_errors.log && \
    touch /var/log/www.access.log && \
    chown -R www-data:www-data /var/log && \
    chmod -R 777 /var/log

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration du répertoire de travail
WORKDIR /var/www

# Copie des fichiers de l'application
COPY . .

# Installation des dépendances Composer avec optimisation
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Configuration des permissions
RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data var && \
    chmod -R 777 var && \
    chmod +x bin/console

# Exposition du port
EXPOSE 9000

CMD ["php-fpm"] 