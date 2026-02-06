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

# Install Python and Composer dependencies
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update --fix-missing \
    && apt-get install -y --no-install-recommends \
    python3 \
    python3-pip \
    python3-venv \
    unzip \
    git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install MeCab with error recovery for CI environments (QEMU emulation issues)
RUN apt-get update \
    && (apt-get install -y --no-install-recommends mecab libmecab-dev mecab-ipadic-utf8 \
        || (dpkg --configure -a && apt-get install -y -f --no-install-recommends mecab libmecab-dev mecab-ipadic-utf8)) \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /usr/local/etc \
    && (test -f /etc/mecabrc && ln -sf /etc/mecabrc /usr/local/etc/mecabrc || true)

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create Python virtual environment and install NLP packages
RUN python3 -m venv /opt/lwt-parsers && \
    /opt/lwt-parsers/bin/pip install --no-cache-dir \
    jieba>=0.42.1 \
    mecab-python3>=1.0.6

# Copy parser scripts first (for better caching)
COPY parsers/ /opt/lwt/parsers/

# Application base path configuration
# Set to /lwt for subdirectory installation, or leave empty for root installation
ARG APP_BASE_PATH=/lwt

# Copy application files
# Files go to /var/www/html{APP_BASE_PATH} (e.g., /var/www/html/lwt or /var/www/html)
COPY . /var/www/html${APP_BASE_PATH}

# Note: Database configuration is provided at runtime via environment variables
# or by mounting a .env file. See docker-compose.yml for examples.

# Configure Apache: enable mod_rewrite and AllowOverride for .htaccess
RUN a2enmod rewrite \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install PHP dependencies
WORKDIR /var/www/html${APP_BASE_PATH}
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend assets (Alpine.js, Vite bundles)
RUN apt-get update && apt-get install -y --no-install-recommends nodejs npm \
    && npm ci --ignore-scripts \
    && npm run build:all \
    && rm -rf node_modules \
    && apt-get purge -y nodejs npm && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

# Set proper ownership for Apache (www-data user)
RUN chown -R www-data:www-data /var/www/html${APP_BASE_PATH}
