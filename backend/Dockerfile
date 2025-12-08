ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-apache-bookworm AS base

LABEL maintainer="matusmartiska24@gmail.com" \
      vendor="LakeWatch" \
      description="PHP Slim backend" \
      version="1.0"

## Update package information
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
 git \
 zlib1g-dev \
 libpq-dev \
 libicu-dev \
 libzip-dev \
 libxml2-dev  \
 libcurl3-dev \
 libonig-dev \
 libmagickwand-dev \
 g++

###
## PHP Extensisons
###

## Install zip libraries and extension
RUN docker-php-ext-install zip fileinfo mbstring curl exif xml filter

## Install PostgreSQL PDO support
RUN docker-php-ext-install pdo pdo_pgsql

RUN apt-get install --yes libapache2-mod-xsendfile

## Configure Apache
RUN a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf

# change the working directory
WORKDIR /var/www/

## Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

FROM base AS development
# copy .ini files for php
COPY ./custom-php.ini "$PHP_INI_DIR/php.ini"

## Install and enable xdebug
RUN pecl install xdebug \
&& docker-php-ext-enable xdebug

# setup xdebug
RUN echo "xdebug.remote.enable=1" >> /usr/local/etc/php/php.ini
COPY ./docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/

FROM base AS production

# copy source files to image
COPY . /var/www/

# install dependencies
RUN composer install --no-dev --no-interaction --no-progress --no-suggest

# Reinstall without dev dependencies for production
RUN composer install --no-dev --optimize-autoloader

# run as non root user
USER www-data