FROM node:25-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY postcss.config.js tailwind.config.js vite.config.js ./
RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize

FROM php:8.3-cli-alpine

WORKDIR /var/www/html

RUN apk add --no-cache bash icu-libs libzip oniguruma \
    && apk add --no-cache --virtual .build-deps icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install intl mbstring pdo_mysql zip \
    && apk del .build-deps

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/start.sh /usr/local/bin/start

RUN chmod +x /usr/local/bin/start \
    && chmod -R ug+rw storage bootstrap/cache

EXPOSE 10000

CMD ["/usr/local/bin/start"]
