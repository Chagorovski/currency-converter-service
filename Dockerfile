# ========= Frontend build =========
FROM node:20-alpine AS fe
WORKDIR /app/frontend
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

# ========= Backend deps (Composer) on a PHP base with extensions =========
# Use a PHP image (same family as runtime) so required extensions are present.
FROM webdevops/php:8.3-alpine AS phpdeps
WORKDIR /app/backend

# Bring in composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# If you need extra extensions at build time, uncomment as needed:
# RUN apk add --no-cache $PHPIZE_DEPS icu-dev \
#  && docker-php-ext-install intl

# Install deps with lock (best for reproducibility)
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# Copy the rest of the app and re-run to dump optimized autoloader & run scripts
COPY backend/ ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader

# ========= Runtime: nginx + php-fpm =========
FROM webdevops/php-nginx:8.3-alpine AS runtime
WORKDIR /app

# Copy backend and vendor from deps stage
COPY --from=phpdeps /app/backend /app/backend

# Copy built frontend into Symfony public/app
COPY --from=fe /app/frontend/dist /app/backend/public/app

# Use your tuned nginx.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Ensure Symfony writable dirs
RUN mkdir -p /app/backend/var/cache /app/backend/var/log /app/backend/var/sessions \
    && chown -R application:application /app/backend/var

ENV WEB_DOCUMENT_ROOT=/app/backend/public
ENV WEB_DOCUMENT_INDEX=index.php
ENV PHP_DATE_TIMEZONE=UTC
ENV PHP_DISPLAY_ERRORS=Off

EXPOSE 80
