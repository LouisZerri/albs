FROM php:8.4-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    intl \
    pdo \
    pdo_mysql \
    zip \
    gd \
    opcache

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installation de Node.js 20.x et npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Configuration Apache
RUN a2enmod rewrite headers

# Copier la configuration VirtualHost
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80
EXPOSE 80