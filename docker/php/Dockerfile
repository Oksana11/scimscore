FROM php:8.4-fpm-alpine

ARG UID=1000
ARG GID=1000

# PHP extensions needed by Laravel + PDF/Excel (gd, zip, intl)
RUN set -eux; \
    apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev linux-headers; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip gd bcmath opcache pcntl exif; \
    apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Non-root user with the same UID/GID as the host user (no root-owned files in the project)
RUN set -eux; \
    if ! getent group "${GID}" >/dev/null; then addgroup -g "${GID}" app; fi; \
    GROUP_NAME="$(getent group "${GID}" | cut -d: -f1)"; \
    if ! getent passwd "${UID}" >/dev/null; then adduser -D -u "${UID}" -G "${GROUP_NAME}" app; fi; \
    mkdir -p /home/app/.composer && chown -R "${UID}:${GID}" /home/app /var/www/html

ENV COMPOSER_HOME=/home/app/.composer

WORKDIR /var/www/html
USER ${UID}:${GID}

EXPOSE 9000
CMD ["php-fpm"]
