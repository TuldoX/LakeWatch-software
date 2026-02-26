ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-apache-bookworm AS base

LABEL maintainer="matusmartiska24@gmail.com" \
      vendor="LakeWatch" \
      description="PHP Slim backend" \
      version="1.0"

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git \
    zlib1g-dev \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    libmagickwand-dev \
    g++ \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip fileinfo mbstring curl exif xml filter pdo pdo_pgsql

RUN a2enmod rewrite \
 && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

FROM base AS production

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN echo "display_errors=Off" > /usr/local/etc/php/conf.d/prod.ini \
 && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/prod.ini \
 && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/prod.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/prod.ini

RUN chown -R www-data:www-data /var/www

USER www-data