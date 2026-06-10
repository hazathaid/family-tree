FROM php:8.3-fpm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

CMD ["php-fpm"]
