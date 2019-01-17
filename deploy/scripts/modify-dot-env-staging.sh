#!/usr/bin/env bash
set -aeuo pipefail

sed -i "/ENVIRONMENT=/c ENVIRONMENT=staging" .env
sed -i '/DOCKER_NETWORK_IPAM_SUBNET/s/^#\ //g' .env
sed -i '/DOCKER_NETWORK_IPAM_GATEWAY/s/^#\ //g' .env
sed -i "/WORDPRESS_WEB_PORT=/c WORDPRESS_WEB_PORT=9993" .env 
sed -i "/APP_ENV=/c APP_ENV=staging" .env
sed -i "/WORDPRESS_SITE_URL=/c WORDPRESS_SITE_URL=https://wordpress-demo.it-consultis.net" .env 
