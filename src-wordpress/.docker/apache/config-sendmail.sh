#!/bin/bash
set -euo pipefail

EXTRA_LINE="127.0.0.1 localhost localhost.localdomain docker.local docker.local.localdomain"

if [ "$INSTALL_SENDMAIL" = "1" ] && [ "$(tail -n 1 /etc/hosts)" != "$EXTRA_LINE" ]; then
  echo "Configuring sendmail"
  echo $EXTRA_LINE >> /etc/hosts
  service sendmail restart
fi
