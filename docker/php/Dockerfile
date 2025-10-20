FROM php:8.3-fpm-alpine

RUN apk add --no-cache git curl bash icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS autoconf make g++ icu-dev libzip-dev libpng-dev oniguruma-dev

RUN docker-php-ext-install intl pdo_mysql opcache

RUN pecl install redis && docker-php-ext-enable redis

RUN apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
CMD ["php-fpm"]
