#!/usr/bin/env bash
set -e

/application/php-kafka-consumer/wait-for-it.sh zookeeper-test:2181

/application/php-kafka-consumer/wait-for-it.sh kafka-test:9092

tail -f /dev/null