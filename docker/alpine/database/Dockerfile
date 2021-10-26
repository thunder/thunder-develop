FROM postgres:14-alpine
ARG DOCKER_USER_ID
ARG DOCKER_GROUP_ID

RUN set -eux; \
  deluser postgres; \
	addgroup -g ${DOCKER_GROUP_ID} -S postgres; \
	adduser -u ${DOCKER_USER_ID} -S -D -G postgres -H -h /var/lib/postgresql -s /bin/sh postgres
