ARG PHP_VERSION=8.3

# ---------- Base PHP-Apache image ----------
FROM php:${PHP_VERSION}-apache-bookworm AS base

LABEL maintainer="matusmartiska24@gmail.com" \
      vendor="LakeWatch" \
      description="PHP Slim backend" \
      version="1.0"

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git \
    libzip-dev \
    zip \
 && docker-php-ext-install zip \
 && a2enmod rewrite \
 && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# ---------- Development image ----------
FROM base AS development
# Copy application source
COPY . /var/www/

# Install PHP deps with dev packages
RUN composer install --no-interaction --prefer-dist

# Useful dev settings
RUN echo "display_errors=1" > $PHP_INI_DIR/conf.d/dev.ini \
 && echo "error_reporting=E_ALL" >> $PHP_INI_DIR/conf.d/dev.ini

# ---------- Production image ----------
FROM base AS production
# Copy application source
COPY . /var/www/

# Install PHP deps without dev, optimized
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run as non-root
USER www-data
