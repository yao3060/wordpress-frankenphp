#!/usr/bin/env bash
set -aeuo pipefail

PARALLEL=
if docker-compose build --help | grep -q 'parallel'; then
   PARALLEL="--parallel"
fi

docker-compose build ${PARALLEL}
docker-compose push
