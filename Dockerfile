FROM php:8.4-cli-alpine3.21

RUN apk add --no-cache \
    sqlite-libs \
    sqlite-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_sqlite bcmath pcntl

COPY --from=composer:2.8.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts

COPY . .

RUN cp -n .env.example .env || true \
    && touch database/database.sqlite \
    && php artisan key:generate --force \
    && php artisan migrate --force

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
