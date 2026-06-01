FROM php:8.2-fpm-alpine AS base

RUN apk add --no-cache \
    bash \
    curl \
    git \
    nginx \
    supervisor \
    redis \
    linux-headers \
    libzip-dev \
    libpng-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo_mysql mbstring zip gd bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY backend/ /var/www/html/

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

FROM base AS frontend

RUN apk add --no-cache nodejs npm

COPY frontend/admin/ /var/www/frontend/admin/
WORKDIR /var/www/frontend/admin
RUN npm ci && npm run build

COPY frontend/wap/ /var/www/frontend/wap/
WORKDIR /var/www/frontend/wap
RUN npm ci && npm run build

FROM base AS production

COPY --from=frontend /var/www/frontend /var/www/frontend
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-custom.ini

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
