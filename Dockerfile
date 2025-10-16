# ========= Frontend build =========
FROM node:20-alpine AS fe
WORKDIR /app/frontend
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

# ========= Backend deps (Composer) =========
FROM composer:2 AS phpdeps
WORKDIR /app/backend
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction
COPY backend/ ./
# ensure vendor matches source in case of scripts/autoload
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# ========= Runtime: nginx + php-fpm in one image =========
# Uses a maintained nginx+php-fpm base (alpine)
FROM webdevops/php-nginx:8.3-alpine

# Workdir for app
WORKDIR /app

# Copy backend app and vendor
COPY --from=phpdeps /app/backend /app/backend

# Copy built frontend into Symfony public/app
COPY --from=fe /app/frontend/dist /app/backend/public/app

# Our nginx config for Fly
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Ensure Symfony writable dirs exist
RUN mkdir -p /app/backend/var/cache /app/backend/var/log /app/backend/var/sessions \
    && chown -R application:application /app/backend/var

# Environment for webdevops image
ENV WEB_DOCUMENT_ROOT=/app/backend/public
ENV WEB_DOCUMENT_INDEX=index.php
ENV PHP_DATE_TIMEZONE=UTC
ENV PHP_DISPLAY_ERRORS=Off

# Fly exposes port via fly.toml; image listens on 80 by default
EXPOSE 80

# HEALTHCHECK (optional)
HEALTHCHECK --interval=30s --timeout=3s --retries=3 CMD wget -qO- http://127.0.0.1/health || exit 1
