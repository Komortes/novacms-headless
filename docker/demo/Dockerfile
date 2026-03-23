FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize

FROM node:20-alpine AS frontend
WORKDIR /app
ARG VITE_APP_NAME=NovaCMS
ARG VITE_REVERB_APP_KEY=localkey
ARG VITE_REVERB_HOST=127.0.0.1
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=http
ENV VITE_APP_NAME=$VITE_APP_NAME
ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-cli-alpine AS app
WORKDIR /app
RUN apk add --no-cache bash curl git icu-dev libpq libzip-dev oniguruma-dev postgresql-client unzip \
    && docker-php-ext-install bcmath intl pcntl pdo_pgsql pgsql sockets zip
COPY --from=vendor /app /app
COPY --from=frontend /app/public/build /app/public/build
COPY docker/demo/entrypoint.sh /usr/local/bin/novacms-demo-entrypoint
RUN chmod +x /usr/local/bin/novacms-demo-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache
ENTRYPOINT ["novacms-demo-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
