# syntax=docker/dockerfile:1.7
#
# CodeLift verification environment for laravel/react-starter-kit.
# Built on php:8.5-cli-bookworm with Node 22 layered on top.
# Upstream source is mounted at /app at runtime — not copied into the image —
# so improvement commits take effect without rebuilding.
#
FROM php:8.5-cli-bookworm

ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip zip curl ca-certificates gnupg \
        libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
        sqlite3 libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath intl pdo_sqlite zip gd exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG NODE_MAJOR=22
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_MAJOR}.x | bash - \
    && apt-get update && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

CMD ["bash", "codelift/verify.sh"]
