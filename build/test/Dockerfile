ARG TAG

FROM mateusjunges/laravel:${TAG}

ARG LARAVEL_VERSION

RUN apk add libzip-dev

RUN apk add unzip

RUN pecl install zip

COPY dev/php.ini /usr/local/etc/php/conf.d

COPY build/composer-files/composer.json-${LARAVEL_VERSION} /application/laravel-test/composer.json

COPY tests /application/laravel-test/tests

COPY build/laravel-kernels/kernel.php /application/laravel-test/app/Console/Kernel.php

COPY dev/kafka.php /application/laravel-test/config

COPY src /application/laravel-kafka/src/

COPY tests /application/laravel-kafka/tests/

COPY composer.json /application/laravel-kafka/composer.json

COPY start.sh /application/laravel-kafka/

COPY wait-for-it.sh /application/laravel-kafka/

RUN cd /application/laravel-kafka && composer update

RUN cd /application/laravel-test && composer update --with-all-dependencies

WORKDIR /application/laravel-test