FROM php:8.0-fpm-alpine

ARG DOCKER_USER_ID
ARG DOCKER_GROUP_ID
ARG ENVIRONMENT

COPY --from=composer:2 /usr/bin/composer /usr/local/bin

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
    pecl install xdebug;  \
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
      pcntl \
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
    apk add --no-cache \
      nginx \
      git \
      bash \
      jq \
      rsync \
      patch \
      mariadb-client \
      zip \
      unzip \
      php-zip \
      openssh-client \
      php-common \
      supervisor; \
    mkdir /opt/project; \
    mkdir /opt/drupal-testing; \
    composer global require --no-cache --working-dir /opt/drupal-testing thunder/drupal-testing:dev-simplify; \
    chown -R www-data.www-data /opt/project; \
    chown -R www-data.www-data /run; \
    chown -R www-data.www-data /var/lib/nginx; \
    chown -R www-data.www-data /var/log/nginx; \
    chown -R www-data.www-data /usr/local/etc/php/conf.d

COPY --chown=www-data:www-data docker/web/config/php/custom.ini /usr/local/etc/php/conf.d/
COPY --chown=www-data:www-data docker/web/config/php/xdebug.ini /usr/local/etc/php/conf.d/
COPY --chown=www-data:www-data docker/web/config/php/opcache-${ENVIRONMENT}.ini /usr/local/etc/php/conf.d/opcache.ini
COPY --chown=www-data:www-data docker/web/config/php/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY --chown=www-data:www-data docker/web/config/nginx/nginx.conf /etc/nginx/nginx.conf
COPY --chown=www-data:www-data docker/web/config/nginx/default.conf /etc/nginx/http.d/default.conf
COPY --chown=www-data:www-data docker/web/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=www-data:www-data docker/certs/ssl.crt /etc/nginx/certs/ssl.crt
COPY --chown=www-data:www-data docker/certs/ssl.key /etc/nginx/certs/ssl.key
COPY --chown=www-data:www-data docker/web/bin/* /usr/local/bin

ENV PATH=/opt/project/vendor/bin:/opt/drupal-testing/vendor/bin:${PATH}

USER www-data
WORKDIR /opt/project

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping
