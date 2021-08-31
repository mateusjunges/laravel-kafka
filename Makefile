CURRENT_DIRECTORY := $(shell pwd)

.PHONY: up stop restart build tail php test coverage laravel-kafka-1 laravel-kafka-2 laravel-kafka-3 laravel-kafka-4 laravel-kafka-5 laravel-kafka-6 laravel-kafka-7 laravel-kafka-8 laravel-kafka-9 laravel-kafka-10 laravel-kafka-11 laravel-kafka-12 laravel-kafka-13 laravel-kafka-14 laravel-kafka-15 laravel-kafka-16 laravel-kafka-17 laravel-kafka-18 laravel-kafka-19 laravel-kafka-20 laravel-kafka-21 laravel-kafka-22 laravel-kafka-23 laravel-kafka-24

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

integration-tests: up
	@docker-compose exec -T laravel ./vendor/phpunit/phpunit/phpunit tests --filter Integration

coverage: up
	@docker-compose exec laravel phpdbg -qrr ./vendor/bin/phpunit tests --whitelist /application/laravel-kafka/src --coverage-html /application/laravel-kafka/coverage

laravel-kafka-%:
	@$(eval TAG = $(@:laravel-kafka-%=%))
	@$(eval LARAVEL_VERSION=$(shell echo ${TAG} | cut -c18))
	@docker-compose -f docker-compose-test.yaml build --build-arg TAG=${TAG} --build-arg LARAVEL_VERSION=${LARAVEL_VERSION}
	@docker-compose -f docker-compose-test.yaml up -d
	@docker-compose -f docker-compose-test.yaml exec -T test ./vendor/phpunit/phpunit/phpunit tests