#!/usr/bin/env bash
set -aeuo pipefail

server=$1
port=$2

CACHE_DATE=$(date +%s)
PARALLEL=

if [ $(docker-compose version --short) == "1.23.1" ]; then
   PARALLEL="--parallel"
fi

docker-compose -f ./docker-compose.yml $(find ./src-* -maxdepth 1 -name 'docker-compose.*' -exec echo '-f {} ' \;) build ${PARALLEL}
docker-compose -f ./docker-compose.yml $(find ./src-* -maxdepth 1 -name 'docker-compose.*' -exec echo '-f {} ' \;) push
ssh -o StrictHostKeyChecking=no root@$server -p $port "echo OK"
ssh root@$server -p $port "mkdir -p /mnt/data/srv/${PROJECT}/${ENVIRONMENT}/"

rsync -e "ssh -p $port" -vI .env docker-compose.yml VERSION ./deploy/resources/* root@$server:/mnt/data/srv/${PROJECT}/${ENVIRONMENT}/
rsync -e "ssh -p $port" -vIR src-*/docker-compose.yml root@$server:/mnt/data/srv/${PROJECT}/${ENVIRONMENT}/

# docker login
ssh root@$server -p $port "docker login -u gitlab-ci-token -p $CI_JOB_TOKEN registry.gitlab.com"
# execute start script && cleanup old images
ssh root@$server -p $port "cd /mnt/data/srv/${PROJECT}/${ENVIRONMENT}/ && ./start && ./cleanup"
