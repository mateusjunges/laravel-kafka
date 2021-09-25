<?php

return [
    /*
     | Your kafka brokers url.
     */
    'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),

    /*
     | Kafka consumers belonging to the same consumer group share a group id.
     | The consumers in a group then divides the topic partitions as fairly amongst themselves as possible by
     | establishing that each partition is only consumed by a single consumer from the group.
     | This config defines the consumer group id you want to use for your project.
     */
    'consumer_group_id' => env('KAFKA_CONSUMER_GROUP_ID', 'group'),

    /*
     | After the consumer receives its assignment from the coordinator,
     | it must determine the initial position for each assigned partition.
     | When the group is first created, before any messages have been consumed, the position is set according to a configurable
     | offset reset policy (auto.offset.reset). Typically, consumption starts either at the earliest offset or the latest offset.
     | You can choose between "latest", "earliest" or "none".
     */
    'offset_reset' => env('KAFKA_OFFSET_RESET', 'latest'),

    /*
     | If you set enable.auto.commit (which is the default), then the consumer will automatically commit offsets periodically at the
     | interval set by auto.commit.interval.ms.
     */
    'auto_commit' => env('KAFKA_AUTO_COMMIT', true),

    'sleep_on_error' => env('KAFKA_ERROR_SLEEP', 5),

    'partition' => env('KAFKA_PARTITION', 0),

    /*
     | Kafka supports 4 compression codecs: none , gzip , lz4 and snappy
     */
    'compression' => env('KAFKA_COMPRESSION_TYPE', 'snappy'),

    /*
     | Choose if debug is enabled or not.
     */
    'debug' => env('KAFKA_DEBUG', false),
];