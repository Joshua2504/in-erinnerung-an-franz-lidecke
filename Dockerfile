FROM php:8.5-apache

RUN apt-get update && apt-get install -y \
        libsqlite3-dev libgd-dev libjpeg-dev libpng-dev libwebp-dev unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_sqlite gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader

RUN a2enmod rewrite headers

RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads

COPY php.ini /usr/local/etc/php/conf.d/uploads.ini

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
