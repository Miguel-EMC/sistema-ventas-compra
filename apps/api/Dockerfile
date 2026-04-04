FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-install \
        dom \
        mbstring \
        pdo_pgsql \
        pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .
COPY docker/start-api.sh /usr/local/bin/start-api.sh

RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && chmod +x /usr/local/bin/start-api.sh

EXPOSE 8001

CMD ["sh", "/usr/local/bin/start-api.sh"]
