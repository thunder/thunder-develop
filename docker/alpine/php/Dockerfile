FROM php:8.0-fpm-alpine as phpFpm
ARG DOCKER_USER_ID
ARG DOCKER_GROUP_ID
ARG ENVIRONMENT
ARG DRUPAL_TESTING_COMPOSER_PROJECT
ARG DRUPAL_TESTING_DATABASE_USER
ARG DRUPAL_TESTING_DATABASE_PASSWORD
ARG DRUPAL_TESTING_DATABASE_HOST
ARG DRUPAL_TESTING_DATABASE_NAME
ARG DRUPAL_TESTING_TEST_DUMP_FILE
ARG DRUPAL_TESTING_DATABASE_ENGINE
ARG DRUPAL_TESTING_TEST_CODING_STYLES
ARG DRUPAL_TESTING_TEST_PROFILE
ARG DRUPAL_TESTING_TEST_GROUP
ARG DRUPAL_TESTING_HTTP_PORT
ARG DRUPAL_TESTING_TEST_FILTER
ARG DRUPAL_TESTING_DRUPAL_VERSION
ARG DRUPAL_TESTING_SELENIUM_HOST
ARG THUNDER_ADMIN_BRANCH
ARG PHPSTAN_MEMORY_LIMIT
ARG DRUPAL_TESTING_PARALLEL_TESTING
ARG SIMPLETEST_BASE_URL

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
      supervisor; \
    mkdir /opt/project; \
    mkdir /opt/drupal-testing; \
    composer global require --no-cache --working-dir /opt/drupal-testing thunder/drupal-testing:dev-simplify; \
    chown -R www-data.www-data /opt/project; \
    chown -R www-data.www-data /run; \
    chown -R www-data.www-data /var/lib/nginx; \
    chown -R www-data.www-data /var/log/nginx

COPY docker/config/php/custom.ini /usr/local/etc/php/conf.d/
COPY docker/config/php/opcache-${ENVIRONMENT}.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/config/php/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/config/web/nginx.conf /etc/nginx/nginx.conf
COPY docker/config/web/default.conf /etc/nginx/http.d/default.conf
COPY docker/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

ENV PATH=/opt/project/vendor/bin:/opt/drupal-testing/vendor/bin:${PATH}
ENV DRUPAL_TESTING_COMPOSER_PROJECT=${DRUPAL_TESTING_COMPOSER_PROJECT}
ENV DRUPAL_TESTING_DATABASE_USER=${DRUPAL_TESTING_DATABASE_USER}
ENV DRUPAL_TESTING_DATABASE_PASSWORD=${DRUPAL_TESTING_DATABASE_PASSWORD}
ENV DRUPAL_TESTING_DATABASE_HOST=${DRUPAL_TESTING_DATABASE_HOST}
ENV DRUPAL_TESTING_DATABASE_NAME=${DRUPAL_TESTING_DATABASE_NAME}
ENV DRUPAL_TESTING_TEST_DUMP_FILE=${DRUPAL_TESTING_TEST_DUMP_FILE}
ENV DRUPAL_TESTING_DATABASE_ENGINE=${DRUPAL_TESTING_DATABASE_ENGINE}
ENV DRUPAL_TESTING_TEST_CODING_STYLES=${DRUPAL_TESTING_TEST_CODING_STYLES}
ENV DRUPAL_TESTING_TEST_PROFILE=${DRUPAL_TESTING_TEST_PROFILE}
ENV DRUPAL_TESTING_TEST_GROUP=${DRUPAL_TESTING_TEST_GROUP}
ENV DRUPAL_TESTING_HTTP_PORT=${DRUPAL_TESTING_HTTP_PORT}
ENV DRUPAL_TESTING_TEST_FILTER=${DRUPAL_TESTING_TEST_FILTER}
ENV DRUPAL_TESTING_DRUPAL_VERSION=${DRUPAL_TESTING_DRUPAL_VERSION}
ENV DRUPAL_TESTING_PARALLEL_TESTING=${DRUPAL_TESTING_PARALLEL_TESTING}
ENV DRUPAL_TESTING_SELENIUM_HOST=${DRUPAL_TESTING_SELENIUM_HOST}
ENV THUNDER_ADMIN_BRANCH=${THUNDER_ADMIN_BRANCH}
ENV PHPSTAN_MEMORY_LIMIT=${PHPSTAN_MEMORY_LIMIT}
ENV SIMPLETEST_BASE_URL=${SIMPLETEST_BASE_URL}

USER www-data
WORKDIR /opt/project

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping
