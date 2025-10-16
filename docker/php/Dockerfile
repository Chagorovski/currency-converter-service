FROM php:8.3-fpm-alpine

# Runtime libs + build deps (for phpize/pecl)
RUN apk add --no-cache git curl bash icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS autoconf make g++ icu-dev libzip-dev libpng-dev oniguruma-dev

# PHP extensions
RUN docker-php-ext-install intl pdo_mysql opcache

# PECL redis
RUN pecl install redis && docker-php-ext-enable redis

# Remove build deps to slim the image
RUN apk del .build-deps

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
CMD ["php-fpm"]
