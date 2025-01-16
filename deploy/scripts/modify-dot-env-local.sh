#!/usr/bin/env bash

sed -i "/DOCKER_HOST_UID=/c DOCKER_HOST_UID=$(id -u)" .env
sed -i "/DOCKER_HOST_GID=/c DOCKER_HOST_GID=$(id -g)" .env
sed -i "/VERSION=/c VERSION=$(cat VERSION)" .env
