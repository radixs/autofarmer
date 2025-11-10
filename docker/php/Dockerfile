FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    nodejs \
    npm \
    mysql-client

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql bcmath intl gd opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
