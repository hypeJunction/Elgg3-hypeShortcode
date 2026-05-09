FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mysqli \
        pdo_mysql \
        xml \
        zip \
        opcache

RUN a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN mkdir -p /var/www/data && chown www-data:www-data /var/www/data

COPY elgg-composer.json /var/www/html/composer.json
RUN cd /var/www/html && composer install --no-interaction --prefer-dist

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

RUN rm -f /var/www/html/elgg-config/settings.php

COPY index.php /var/www/html/index.php
RUN cp vendor/elgg/elgg/install/config/htaccess.dist /var/www/html/.htaccess \
    && mkdir -p /var/www/html/mod

COPY elgg-install.sh /usr/local/bin/elgg-install.sh
RUN chmod +x /usr/local/bin/elgg-install.sh

EXPOSE 80
