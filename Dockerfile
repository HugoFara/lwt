FROM php:8.4-apache-bookworm

LABEL org.opencontainers.image.title="LWT Community"
LABEL org.opencontainers.image.description="An image for LWT"
LABEL org.opencontainers.image.documentation="https://hugofara.github.io/lwt/docs/"
LABEL org.opencontainers.image.url="https://hugofara.github.io/lwt/"
LABEL org.opencontainers.image.author="HugoFara <contact@hugofara.net>"
LABEL org.opencontainers.image.license=Unlicense
LABEL org.opencontainers.image.source="https://github.com/HugoFara/lwt"


# creating config file php.ini 
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo 'mysqli.allow_local_infile = On' >> "$PHP_INI_DIR/php.ini"; \
    docker-php-ext-install pdo pdo_mysql mysqli

COPY . /var/www/html/lwt

# creating .env configuration file
ARG DB_HOSTNAME=db
ARG DB_USER=root
ARG DB_PASSWORD=root
ARG DB_DATABASE=learning-with-texts

RUN printf 'DB_HOST=%s\nDB_USER=%s\nDB_PASSWORD=%s\nDB_NAME=%s\n' \
    "$DB_HOSTNAME" \
    "$DB_USER" \
    "$DB_PASSWORD" \
    "$DB_DATABASE" \
    > /var/www/html/lwt/.env
