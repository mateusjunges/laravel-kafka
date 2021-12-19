CURRENT_DIRECTORY := $(shell pwd)

.PHONY: up stop restart build tail php test coverage version-test-1 version-test-2 version-test-3 version-test-4 version-test-5 version-test-6 version-test-7 version-test-8 version-test-9 version-test-10 version-test-11 version-test-12 version-test-13 version-test-14 version-test-15 version-test-16 version-test-17 version-test-18 version-test-19 version-test-20 version-test-21 version-test-22 version-test-23 version-test-24

up:
	@docker-compose up -d

stop:
	@docker-compose stop

restart: stop up

build:
	@docker-compose up -d --build

tail:
	@docker-compose logs -f

laravel:
	@docker-compose exec laravel bash

test: up
	@docker-compose exec -T laravel ./vendor/phpunit/phpunit/phpunit tests -c phpunit.xml

unit-tests: up
	@docker-compose exec -T laravel ./vendor/phpunit/phpunit/phpunit tests --filter Unit

integration-tests: up
	@docker-compose exec -T laravel ./vendor/phpunit/phpunit/phpunit tests --filter Integration

coverage: up
	@docker-compose exec laravel phpdbg -qrr ./vendor/bin/phpunit tests --whitelist /application/laravel-kafka/src --coverage-html /application/laravel-kafka/coverage

unit-coverage:
	@docker-compose exec laravel phpdbg -qrr ./vendor/bin/phpunit tests --whitelist /application/laravel-kafka/src --coverage-html /application/laravel-kafka/coverage --filter Unit

integration-coverage:
	@docker-compose exec laravel phpdbg -qrr ./vendor/bin/phpunit tests --whitelist /application/laravel-kafka/src --coverage-html /application/laravel-kafka/coverage --filter Integration

version-test-%:
	@$(eval TAG = $(@:version-test-%=%))
	@$(eval LARAVEL_VERSION=$(shell echo ${TAG} | cut -c18))
	@$(eval PHP_VERSION=$(shell echo ${TAG:0:3}))
	@$(eval LIBRDKAFKA_VERSION=$(shell echo ${TAG:4:6}))
	@$(eval EXT_RDKAFKA_VERSION=$(shell echo ${TAG:11:5}))
	@docker-compose -f docker-compose-test.yaml build --build-arg PHP_VERSION=${PHP_VERSION} --build-arg TAG=${TAG} --build-arg LARAVEL_VERSION=${LARAVEL_VERSION} --build-arg LIBRDKAFKA_VERSION=${LIBRDKAFKA_VERSION} --build-arg EXT_RDKAFKA_VERSION=${EXT_RDKAFKA_VERSION}
	@docker-compose -f docker-compose-test.yaml up -d
	@docker-compose -f docker-compose-test.yaml exec -T test ./vendor/phpunit/phpunit/phpunit tests