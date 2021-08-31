#!/usr/bin/env bash
set -e

/application/laravel-kafka/wait-for-it.sh zookeeper-test:2181

/application/laravel-kafka/wait-for-it.sh kafka-test:9092

tail -f /dev/null