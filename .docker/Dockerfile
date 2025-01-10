ARG PHP_VERSION=8.4
ARG USER=www-data

FROM dunglas/frankenphp:builder AS builder

# Copy xcaddy in the builder image
COPY --from=caddy:builder /usr/bin/xcaddy /usr/bin/xcaddy

# CGO must be enabled to build FrankenPHP
# 启用 CGO（C Go），允许 Go 代码调用 C 代码，这对于 PHP 集成是必需的
RUN CGO_ENABLED=1 \
    # 设置二进制文件的 capabilities，允许服务器绑定特权端口（如 80/443）
    XCADDY_SETCAP=1 \
    # 设置 Go 编译标志
    # - -w -s：减小二进制文件大小（去除调试信息）
    # - nobadger,nomysql,nopgx：排除不需要的数据库驱动
    XCADDY_GO_BUILD_FLAGS="-ldflags='-w -s' -tags=nobadger,nomysql,nopgx" \
    # 设置 C 编译器标志，包含 PHP 的头文件和库文件路径， 这些是编译 PHP 扩展所必需的。
    CGO_CFLAGS=$(php-config --includes) \
    CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs)" \
    xcaddy build \
        --output /usr/local/bin/frankenphp \
        --with github.com/dunglas/frankenphp=./ \
        --with github.com/dunglas/frankenphp/caddy=./caddy/ \
        --with github.com/dunglas/caddy-cbrotli \
        # Mercure and Vulcain are included in the official build, but feel free to remove them
        # Mercure 是一个实时通信协议，
        # 允许服务器向客户端推送更新，
        # 可以用于实现实时通知、聊天、实时数据更新等功能，
        # 比传统的 WebSocket 更轻量和灵活，
        # 特别适合需要实时更新的 WordPress 应用（如实时评论、通知等）
        # --with github.com/dunglas/mercure/caddy \
        # Vulcain 是一个协议，用于优化 HTTP API 的性能，
        # 允许客户端预先获取（preload）相关资源，
        # 减少不必要的数据传输，
        # 可以显著提升 API 的性能和响应速度
        --with github.com/dunglas/vulcain/caddy
        # Add extra Caddy modules here

FROM dunglas/frankenphp AS runner

# Replace the official binary by the one contained your custom modules
COPY --from=builder /usr/local/bin/frankenphp /usr/local/bin/frankenphp
ENV PHP_INI_SCAN_DIR=$PHP_INI_DIR/conf.d

# add additional extensions here:
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
    echo 'opcache.revalidate_freq=2'; \
    } > $PHP_INI_DIR/conf.d/opcache-recommended.ini
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

COPY ./.docker/caddy/Caddyfile /etc/caddy/Caddyfile

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

USER www-data

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]