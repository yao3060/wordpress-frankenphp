#!/bin/bash
set -euo pipefail


if [ "${DEV_ENV}" = "1" ]; then
  echo "DEV MODE: Symlink files from wordpress source /usr/src/wordpress to /var/www/html"

  setup-wp-source.sh dev &
else
  # Run it after wordpress copy it source code
  if [ ! -f /.copied ]; then
    echo "PROD MODE: Copy files from wordpress source /usr/src/wordpress to /var/www/html"

    setup-wp-source.sh prod &

    touch /.copied
  fi
fi

config-sendmail.sh &

docker-entrypoint.sh "$@"
