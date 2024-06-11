FROM php:8.3-cli-alpine

ENV PHP_MEMORY_LIMIT 512M
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV JOB_INTERVAL */5 * * * *

RUN apk add --no-cache bash \
    && apk add --no-cache --virtual .build-deps ${PHPIZE_DEPS} tzdata libzip-dev libxml2-dev linux-headers \
    # Change localtime and timezone
    && cp /usr/share/zoneinfo/Europe/Amsterdam /etc/localtime \
    && echo "Europe/Amsterdam" > /etc/timezone \
    # Install composer
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
    # Install php extensions. Speedup by using -j
    && docker-php-ext-install -j$(nproc) zip \
    # Install runtime dependencies
    && runtime_deps="$(scanelf --needed --nobanner --recursive /usr/local | awk '{ gsub(/,/, "\nso:", $2); print "so:" $2 }' | sort -u | xargs -r apk info --installed | sort -u)" \
    && apk add --no-cache --virtual .runtime-deps ${runtime_deps} \
    # Cleanup build dependencies
    && apk del .build-deps

# Copy source
WORKDIR /src
COPY src .

# Install packages from Composer
RUN composer install --no-dev --no-interaction

CMD [ "bash", "cmd.sh"]