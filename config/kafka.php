<?php

return [
    'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
    'consumer_group_id' => env('KAFKA_CONSUMER_GROUP_ID', 'group'),
    'offset_reset' => env('KAFKA_OFFSET_RESET', 'latest'),
    'auto_commit' => env('KAFKA_AUTO_COMMIT', true),
    'sleep_on_error' => env('KAFKA_ERROR_SLEEP', 5),
    'partition' => env('KAFKA_PARTITION', 0),
    'compression' => env('KAFKA_COMPRESSION_TYPE', 'snappy'),
    'debug' => env('KAFKA_DEBUG', false),
    'topic_prefix' => env('KAFKA_TOPIC_PREFIX'),
];