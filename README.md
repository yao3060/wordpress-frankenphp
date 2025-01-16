# Boilerplate Base

Generate your boilerplate on the boilerplate generator: https://boilerplate.it-consultis.net


## Configure project name, environment and HTTP Hosts

```
vim .env.default
```

## Start project

```
./start
```

All `docker-compose up` options available in `./start`
Additional Options for `./start`

`--skip-install` to skip install dependencies 
`--app xxx --app yyy` to start specific docker in specific source folders instead of whole project

E.g. 

`./start -d` to start in detach mode
`./start -d --force-recreate` to start in detach mode and recreate
`./start --app [app-name] -d` to start just specific app docker, available value will be all of `src-*` folder
`./start --skip-install -d` to start docker as daemon mode and skip modules installation

For example:

`./start --app react` to start all docker inside `src-react` folder
`./start --app laravel --app react` to start all docker inside `src-laravel` folder and `src-react` folder

Start project with no-dev option - simulate to production environment

`./start --no-dev`

Or

`./docker-compose --no-dev up -d --force-recreate --remove-orphans` to start built docker container

## Stop project

```
./stop
```

> Note: Don't run stop unless you want to remove entries docker images and volumes inside your local machine
> Alternative way: can be done is run `./docker-compose down` to remove all docker containers, It will leave docker built images and next time `./docker-compose up -d` can be used to create and start containers again
> See `docker-guide.md` for more docker commands

## Global service containers

You can add global service containers like `prerender`, `imaginary`, `nginx proxy`, ... inside the root docker-compose.yml.

Prerender configuration: https://git.it-consultis.net/docker/prerender#project-configuration


```bash
docker run --rm -it -w /app/public \
   -v $PWD/src:/app/public -p 8000:80 \
   -v $PWD/.docker/8.3/Caddyfile:/Caddyfile \
   dunglas/frankenphp:latest-php8.3 bash -c "frankenphp run --config /Caddyfile"


docker run --rm -it  -v $PWD/src:/app dunglas/frankenphp bash
```
