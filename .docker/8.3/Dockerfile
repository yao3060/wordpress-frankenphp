ARG PHP_VERSION=8.3
ARG USER=www-data

FROM composer:2.7.7 as composer

# # copy source code to image
COPY ./src /app
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader -vvv


FROM dunglas/frankenphp:latest-php${PHP_VERSION}  AS runner

ARG ENVIRONMENT

ENV PHP_INI_SCAN_DIR=$PHP_INI_DIR/conf.d

RUN install-php-extensions \
	bcmath \
	gd \
	intl \
	zip \
	opcache \
  redis \
  mysqli

RUN cp $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY ./.docker/php.ini $PHP_INI_DIR/conf.d/wp.ini

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN set -eux; \
    { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.enable_cli=1'; \
    } > $PHP_INI_DIR/conf.d/opcache-recommended.ini

# override opcache configuration for dev environment
RUN if [ "$ENVIRONMENT" = "local" ]; then \
      sed -i "/opcache.revalidate_freq/c opcache.revalidate_freq=2" /usr/local/etc/php/conf.d/opcache-recommended.ini; \
    fi

# https://wordpress.org/support/article/editing-wp-config-php/#configure-error-logging

RUN { \
    # https://www.php.net/manual/en/errorfunc.constants.php
    # https://github.com/docker-library/wordpress/issues/420#issuecomment-517839670
    echo 'error_reporting = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_RECOVERABLE_ERROR'; \
    echo 'display_errors = Off'; \
    echo 'display_startup_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
    echo 'log_errors_max_len = 1024'; \
    echo 'ignore_repeated_errors = On'; \
    echo 'ignore_repeated_source = Off'; \
    echo 'html_errors = Off'; \
    } > $PHP_INI_DIR/conf.d/error-logging.ini



WORKDIR /var/www/html

# Adding WordPress CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && \
    mv wp-cli.phar /usr/local/bin/wp && \
    mkdir -p /var/www/.wp-cli

COPY ./.docker/8.3/Caddyfile /etc/caddy/Caddyfile

# copy source code
COPY ./src /var/www/html

# copy composer source code
COPY --from=composer /app/vendor /var/www/html/vendor
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN /usr/bin/composer dump-autoload

COPY ./.docker/wp-config-docker.php /var/www/html/wp-config.php

# Running as a Non-Root User
# @see https://frankenphp.dev/docs/docker/#running-as-a-non-root-user
# @see https://frankenphp.dev/docs/docker/#running-with-no-capabilities
RUN \
	# Use "adduser -D ${USER}" for alpine based distros
	# useradd ${USER}; \
    # Add additional capability to bind to port 80 and 443
    setcap -r /usr/local/bin/frankenphp; \
    # Give write access to /data/caddy and /config/caddy
    chown -R www-data:www-data /data/caddy && chown -R www-data:www-data /config/caddy && chown -R www-data:www-data /var/www

# copy start and init scripts to image
COPY ./.docker/start /start

USER www-data

CMD ["/start"]
