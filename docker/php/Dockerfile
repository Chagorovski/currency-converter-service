FROM php:8.3-fpm-alpine

RUN apk add --no-cache git curl bash icu-libs libzip oniguruma openssl \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS autoconf make g++ icu-dev libzip-dev oniguruma-dev curl-dev openssl-dev

RUN docker-php-ext-install intl curl mbstring zip pdo_mysql opcache

RUN pecl install redis && docker-php-ext-enable redis

RUN apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
CMD ["php-fpm"]
