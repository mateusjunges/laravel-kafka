
FROM joesantos386/laravel:8.0-v1.5.3-5.0.0-8

RUN apk add libzip-dev

RUN apk add unzip

RUN pecl install zip

COPY dev/php.ini /usr/local/etc/php/conf.d

COPY build/composer-files/composer.json-8 /application/laravel-test/composer.json

COPY build/laravel-kernels/kernel.php /application/laravel-test/app/Console/Kernel.php

COPY dev/kafka.php /application/laravel-test/config

COPY composer.json /application/php-kafka-consumer/composer.json

COPY src /application/php-kafka-consumer/src/

WORKDIR /application/laravel-test

RUN cd /application/php-kafka-consumer && composer update

RUN cd /application/laravel-test && composer update