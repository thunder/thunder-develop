FROM nginx:alpine
ARG DOCKER_USER_ID
ARG DOCKER_GROUP_ID
ARG ENVIRONMENT

COPY docker/config/web/default.conf /etc/nginx/conf.d/default.conf

RUN set -eux; \
    \
    deluser nginx; \
    addgroup --gid ${DOCKER_GROUP_ID} nginx; \
    adduser --disabled-password --gecos '' --uid ${DOCKER_USER_ID} -G nginx nginx

WORKDIR /opt/app
