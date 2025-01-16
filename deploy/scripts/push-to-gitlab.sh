#!/usr/bin/env bash
set -aeuo pipefail

PARALLEL=
if docker-compose build --help | grep -q 'parallel'; then
   PARALLEL="--parallel"
fi

docker-compose -f ./docker-compose.yml build ${PARALLEL}
docker-compose -f ./docker-compose.yml push
