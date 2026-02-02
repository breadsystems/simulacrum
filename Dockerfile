FROM composer:2 AS composer

# Install GD extension and its dependencies
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev -vvv

FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Apache docroot = /var/www/html
COPY public/* html/

COPY bin bin
COPY php.ini /usr/local/etc/php/conf.d/php.ini
COPY README.md README.md
COPY src src
COPY --from=composer /app/vendor ./vendor

# Configure modrewrite
RUN a2enmod rewrite
COPY apache/rewrite.conf /etc/apache2/conf-available/rewrite.conf
RUN a2enconf rewrite

RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

VOLUME ["/var/www/uploads"]
ENV IMAGES_ROOT=/var/www/uploads
