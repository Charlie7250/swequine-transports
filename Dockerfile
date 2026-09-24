FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json .npmrc vite.config.js ./
COPY resources ./resources

RUN npm install
RUN npm run build

FROM php:8.5-cli-bookworm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends git libpq-dev libsqlite3-dev unzip \
    && docker-php-ext-install bcmath pdo_pgsql pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

COPY --from=frontend /app/public/build ./public/build

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
