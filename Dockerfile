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
 g++ \
 libapache2-mod-xsendfile \
 && rm -rf /var/lib/apt/lists/*

###
## PHP Extensions
###
RUN docker-php-ext-install zip fileinfo mbstring curl exif xml filter pdo pdo_pgsql

## Configure Apache
RUN a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf

# Change the working directory
WORKDIR /var/www/

## Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# ============================================
# DEVELOPMENT STAGE
# ============================================
FROM base AS development

## Install and enable xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

## Configure Xdebug for development
RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

## PHP development settings
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/dev.ini \
    && echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/dev.ini \
    && echo "opcache.enable=0" >> /usr/local/etc/php/conf.d/dev.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/dev.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/dev.ini

# Run as www-data for development
USER www-data

# ============================================
# PRODUCTION STAGE
# ============================================
FROM base AS production

# Copy source files to image
COPY --chown=www-data:www-data . /var/www/

# Install dependencies without dev packages
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

## PHP production settings
RUN echo "display_errors=Off" >> /usr/local/etc/php/conf.d/prod.ini \
    && echo "error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/prod.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/prod.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/prod.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/prod.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/prod.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/prod.ini

# Run as non root user
USER www-data