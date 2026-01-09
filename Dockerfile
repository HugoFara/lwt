FROM php:8.4-apache-bookworm

LABEL org.opencontainers.image.title="LWT Community"
LABEL org.opencontainers.image.description="An image for LWT"
LABEL org.opencontainers.image.documentation="https://hugofara.github.io/lwt/docs/"
LABEL org.opencontainers.image.url="https://hugofara.github.io/lwt/"
LABEL org.opencontainers.image.author="HugoFara <contact@hugofara.net>"
LABEL org.opencontainers.image.license=Unlicense
LABEL org.opencontainers.image.source="https://github.com/HugoFara/lwt"


# Creating config file php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo 'mysqli.allow_local_infile = On' >> "$PHP_INI_DIR/php.ini"; \
    docker-php-ext-install pdo pdo_mysql mysqli

# Install Python and MeCab for NLP parsing
ENV DEBIAN_FRONTEND=noninteractive
RUN rm -rf /var/lib/apt/lists/* \
    && apt-get clean \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
    python3 \
    python3-pip \
    python3-venv \
    && apt-get install -y --no-install-recommends \
    mecab \
    libmecab-dev \
    mecab-ipadic-utf8 \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /usr/local/etc \
    && (test -f /etc/mecabrc && ln -sf /etc/mecabrc /usr/local/etc/mecabrc || true)

# Create Python virtual environment and install NLP packages
RUN python3 -m venv /opt/lwt-parsers && \
    /opt/lwt-parsers/bin/pip install --no-cache-dir \
    jieba>=0.42.1 \
    mecab-python3>=1.0.6

# Copy parser scripts first (for better caching)
COPY parsers/ /opt/lwt/parsers/

# Copy application files
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
