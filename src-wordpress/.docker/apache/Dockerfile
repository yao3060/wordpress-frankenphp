ARG WORDPRESS_VERSION=5.0.3
FROM wordpress:${WORDPRESS_VERSION}

# Use WP-CLI container instead
#RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
#  chmod +x wp-cli.phar && \
#  mv wp-cli.phar /usr/local/bin/wp && chmod +x /usr/local/bin/wp

# Install neccessary softwares
RUN apt-get update && apt-get install -y vim && rm -rf /var/lib/apt/lists/*

# Install sendmail
ARG INSTALL_SENDMAIL
RUN if [ "$INSTALL_SENDMAIL" = "1" ]; then \
  echo "Installing sendmail"; \
  apt-get update && apt-get install -y sendmail && rm -rf /var/lib/apt/lists/*; \
fi
ENV INSTALL_SENDMAIL=$INSTALL_SENDMAIL

# prevent caching
ARG CACHE_DATE
ARG XDEBUG
RUN echo "CACHE_DATE -> $CACHE_DATE, XDEBUG -> '$XDEBUG'"

ENV XDEBUG_VERSION=2.6.1

# install xdebug
RUN if [ "$XDEBUG" = "1" ]; then \
    echo "Installing xdebug" && \
    pecl install xdebug-$XDEBUG_VERSION && \
    docker-php-ext-enable xdebug && \
    echo 'xdebug.remote_enable = 1 \n\
xdebug.remote_port = 9001 \n\
xdebug.idekey = docker \n\
xdebug.remote_connect_back = 0 \n\
xdebug.show_error_trace = 1 \n\
xdebug.remote_autostart = 1 \n\
' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    rm -rf /tmp/*; \
fi

# Add current user [not root] to group www-data to prevent permission issue - Mostly on local
ARG DOCKER_HOST_UID
RUN echo "DOCKER_HOST_UID -> $DOCKER_HOST_UID" && if [ ! $DOCKER_HOST_UID = "0" ]; then echo "add user $DOCKER_HOST_UID to www-data group "; adduser --system --disabled-password --no-create-home --disabled-login  -uid $DOCKER_HOST_UID --force-badname --ingroup www-data $DOCKER_HOST_UID; fi

# Bin
COPY ./src-wordpress/.docker/apache/install-rest-api-plugins /usr/local/bin/install-rest-api-plugins
COPY ./src-wordpress/.docker/apache/wp-docker-entrypoint.sh /usr/local/bin/wp-docker-entrypoint.sh
COPY ./src-wordpress/.docker/apache/setup-wp-source.sh /usr/local/bin/setup-wp-source.sh
COPY ./src-wordpress/.docker/apache/config-sendmail.sh /usr/local/bin/config-sendmail.sh
COPY ./src-wordpress/.docker/apache/usr/local/etc/php/conf.d/sendmail.ini /usr/local/etc/php/conf.d/sendmail.ini

RUN chmod +x /usr/local/bin/install-rest-api-plugins /usr/local/bin/wp-docker-entrypoint.sh /usr/local/bin/setup-wp-source.sh /usr/local/bin/config-sendmail.sh

# copy source code and config to image
COPY ./src-wordpress/src/wp-config-sample.php /usr/src/wordpress/wp-config-sample.php
COPY ./src-wordpress/src/index.php /usr/src/wordpress/index.php
COPY ./src-wordpress/src/.htaccess /usr/src/wordpress/.htaccess
COPY ./src-wordpress/src/wp-content /usr/src/wordpress/wp-content
COPY ./.env /usr/src/wordpress/.env

ENTRYPOINT ["wp-docker-entrypoint.sh"]

CMD ["apache2-foreground"]
