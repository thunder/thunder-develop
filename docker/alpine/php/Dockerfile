FROM php:8.0-fpm-alpine as phpFpm
ARG DOCKER_USER_ID
ARG DOCKER_GROUP_ID
ARG ENVIRONMENT

RUN set -eux; \
	  \
	  mv "${PHP_INI_DIR}/php.ini-${ENVIRONMENT}" "${PHP_INI_DIR}/php.ini"; \
    deluser www-data; \
    addgroup --gid ${DOCKER_GROUP_ID} www-data; \
    adduser --disabled-password --gecos '' --uid ${DOCKER_USER_ID} -G www-data www-data; \
    \
    apk add --no-cache --virtual .build-deps \
      coreutils \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
      libzip-dev \
      autoconf \
      g++ \
      make \
      postgresql-dev \
      imagemagick-dev \
    ; \
    \
    pecl install imagick;  \
    \
    docker-php-ext-enable imagick; \
    docker-php-ext-configure gd \
      --with-freetype \
      --with-jpeg=/usr/include \
    ; \
    \
    docker-php-ext-install -j "$(nproc)" \
      gd \
      opcache \
      pdo_mysql \
      pdo_pgsql \
      zip \
    ; \
    \
    runDeps="$( \
      scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
        | tr ',' '\n' \
        | sort -u \
        | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )"; \
    apk add --no-network --virtual .drupal-phpexts-rundeps $runDeps; \
    apk del --no-network .build-deps; \
    apk add --no-cache mariadb-client

COPY docker/config/php/custom.ini /usr/local/etc/php/conf.d/
COPY docker/config/php/opcache-${ENVIRONMENT}.ini /usr/local/etc/php/conf.d/opcache.ini

USER www-data
WORKDIR /opt/app
