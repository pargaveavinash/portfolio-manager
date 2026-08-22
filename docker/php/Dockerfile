FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        pdo_pgsql \
        zip \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html