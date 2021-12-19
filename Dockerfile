FROM php:7.4-apache-bullseye

RUN set -eux; \
    \
    SU_EXEC_VERSION=212b75144bbc06722fbd7661f651390dc47a43d1; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        unzip \
    ; \
    rm -rf /var/lib/apt/lists/*; \
    \
    curl -fsSL -o su-exec.tar.gz "https://github.com/ncopa/su-exec/archive/$SU_EXEC_VERSION.tar.gz"; \
    tar -xf su-exec.tar.gz; \
    rm su-exec.tar.gz; \
    \
    make -C "su-exec-$SU_EXEC_VERSION"; \
    mv "su-exec-$SU_EXEC_VERSION/su-exec" /usr/local/bin; \
    rm -r "su-exec-$SU_EXEC_VERSION"

RUN set -ex; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libzip-dev \
        libgmp-dev \
    ; \
    docker-php-ext-install -j "$(nproc)" \
        bcmath \
        intl \
        opcache \
        pcntl \
        zip \
        gmp \
    ; \
    pecl install APCu-5.1.21; \
    docker-php-ext-enable \
        apcu \
    ; \
    rm -r /tmp/pear; \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
    ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { print $3 }' \
        | sort -u \
        | xargs -r dpkg-query -S \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    # https://getcomposer.org/
    COMPOSER_VERSION=2.1.14; \
    # https://github.com/composer/getcomposer.org/blob/master/web/installer
    COMPOSER_INSTALLER_VERSION=ce43e63e47a7fca052628faf1e4b14f9100ae82c; \
    curl -fsSL "https://raw.githubusercontent.com/composer/getcomposer.org/$COMPOSER_INSTALLER_VERSION/web/installer" | php -- --quiet --install-dir=/usr/local/bin --filename=composer --version="$COMPOSER_VERSION"; \
    mkdir -p /var/www/.composer; \
    chown -R www-data:www-data /var/www/.composer; \
    { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.save_comments=1'; \
        echo 'opcache.revalidate_freq=2'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini; \
    echo 'apc.enable_cli=1' >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini; \
    echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/memory-limit.ini; \
    echo 'max_execution_time=60' > /usr/local/etc/php/conf.d/max-execution-time.ini

COPY --chown=www-data:www-data . /var/www/html

RUN set -eux; \
    composer install -d  /var/www/html -o --no-dev

ENV CFP_HOST_KEY=""
ENV CFP_TITLE="Cloudflare"
