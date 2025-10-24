FROM php:8.2-fpm-alpine

# Required system dependencies
RUN apk add --no-cache postgresql-dev curl git zip unzip

# PHP extensions for PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy app files
COPY . .

# Data ownership
RUN chown -R www-data:www-data /var/www/html
