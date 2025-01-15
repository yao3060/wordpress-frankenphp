# README

## Frankenphp vs Apache
```bash
ab -n 1000 -c 50 http://localhost:8081/wp-json/wp/v2/posts
```

![alt text](image-1.png)

## Build

```shell

# Build on PHP 8.4
docker build --tag yao3060/wordpress-frankenphp:php8.4  -f ./.docker/Dockerfile .

# Build on PHP 8.3
docker build --tag yao3060/wordpress-frankenphp:php8.3  -f ./.docker/Dockerfile.8.3 .
```
