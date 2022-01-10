FROM mateusjunges/laravel:8.0-v1.5.3-5.0.0-8

RUN apk add libzip-dev

RUN apk add unzip

RUN pecl install zip

COPY dev/php.ini /usr/local/etc/php/conf.d

COPY build/composer-files/composer.json-8 /application/laravel-test/composer.json

COPY build/laravel-kernels/kernel.php /application/laravel-test/app/Console/Kernel.php

COPY dev/kafka.php /application/laravel-test/config

COPY composer.json /application/laravel-kafka/composer.json

COPY src /application/laravel-kafka/src/

WORKDIR /application/laravel-test

RUN cd /application/laravel-kafka && composer update

RUN cd /application/laravel-test && composer update