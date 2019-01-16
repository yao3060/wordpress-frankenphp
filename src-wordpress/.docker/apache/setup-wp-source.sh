#!/bin/bash
set -euo pipefail

CMD="cp -rfuv"

if [ "$1" = "dev" ]; then
  CMD="ln -sfv"
fi

# Sample config
if [ ! -f /var/www/html/wp-config.php ]; then
  $CMD /usr/src/wordpress/wp-config-sample.php /var/www/html/wp-config-sample.php
fi

# run after apache 2 ready - parent docker image docker entrypoint run finish
if [ ! -f wp-includes/version.php ]; then
  echo "Waiting for apache2 ready"

  until pids=$(pidof apache2)
  do
    sleep 1
  done

  echo "Apache2 process started"
fi

$CMD /usr/src/wordpress/index.php /var/www/html/index.php
$CMD /usr/src/wordpress/.htaccess /var/www/html/.htaccess
$CMD /usr/src/wordpress/.env /var/www/html/.env

# Only copy wp-content when prod - not mounted volume
if [ "$1" != "dev" ]; then
  $CMD /usr/src/wordpress/wp-content /var/www/html/
fi

chown www-data:www-data -R /var/www/html/wp-content \
  /var/www/html/index.php \
  /var/www/html/wp-config-sample.php \
  /var/www/html/.htaccess \
  /var/www/html/.env

echo "Config source done by using ${CMD}!"
