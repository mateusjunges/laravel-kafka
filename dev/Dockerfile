FROM mateusjunges/laravel:8.3-v2.3.0-6.0.3-10

RUN apk add libzip-dev

RUN apk add unzip

RUN pecl install zip

COPY dev/php.ini /usr/local/etc/php/conf.d

COPY build/composer-files/composer.json-10 /application/laravel-test/composer.json

COPY build/laravel-kernels/kernel.php.stub /application/laravel-test/app/Console/Kernel.php

COPY dev/kafka.php /application/laravel-test/config

COPY composer.json /application/laravel-kafka/composer.json

COPY src /application/laravel-kafka/src/

WORKDIR /application/laravel-test

RUN cd /application/laravel-kafka && composer update

RUN cd /application/laravel-test && composer update