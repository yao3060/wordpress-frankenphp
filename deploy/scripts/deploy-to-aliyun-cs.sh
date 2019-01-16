#!/usr/bin/env bash
set -aeuo pipefail

IMAGES=$@

docker-compose -f ./docker-compose.yml $(find ./src-* -maxdepth 1 -name 'docker-compose.*' -exec echo '-f {} ' \;) build 

docker login --username=$ALIYUN_USERNAME $ALIYUN_CONTAINER_REGISTRY --password=$DOCKER_PASSWORD

for image in ${IMAGES[*]}
do
    docker tag ${CONTAINER_REGISTRY}/${ENVIRONMENT}_$image:${VERSION} $ALIYUN_CONTAINER_REGISTRY/$image:${VERSION}
	docker push $ALIYUN_CONTAINER_REGISTRY/$image:${VERSION}
	docker tag ${CONTAINER_REGISTRY}/${ENVIRONMENT}_$image:${VERSION} $ALIYUN_CONTAINER_REGISTRY/$image:latest
	docker push $ALIYUN_CONTAINER_REGISTRY/$image:latest
done
