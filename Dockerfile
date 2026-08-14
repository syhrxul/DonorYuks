FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        oniguruma-dev \
        curl-dev \
        libxml2-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
    && docker-php-ext-enable opcache \
    && apk del $PHPIZE_DEPS

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["/var/www/html/docker/entrypoint.sh"]