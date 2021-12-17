<?php

return [
    /*
     | Define your Kafka clusters configuration here.
     */
    'clusters' => [
        'default' => [
            /*
            | Your kafka brokers url.
            */
            'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),

            /*
            | Kafka supports 4 compression codecs: none , gzip , lz4 and snappy
            */
            'compression' => env('KAFKA_COMPRESSION_TYPE', 'snappy'),
            /*
             | Choose if debug is enabled or not.
             */
            'debug' => env('KAFKA_DEBUG', false),
            'security_protocol' => env('KAFKA_SECURITY_PROTOCOL', 'plaintext'),
            /*
             * The custom configuration options for this cluster.
             */
            'options' => [],
        ]
    ],

    /*
     | Define your consumers configuration here.
     */
    'consumers' => [
        'default' => [
            /*
            | Your kafka brokers url.
            */
            'brokers' => 'localhost:9092',

            /*
             | The topics this consumer should consume messages from.
             */
            'topics' => [],

            /*
             * The dead letter queue for this consumer.
             */
            'dlq_topic' => null,

            /*
             | Kafka consumers belonging to the same consumer group share a group id.
             | The consumers in a group then divides the topic partitions as fairly amongst themselves as possible by
             | establishing that each partition is only consumed by a single consumer from the group.
             | This config defines the consumer group id you want to use for your project.
             */
            'group_id' => null,

            /*
            | After the consumer receives its assignment from the coordinator,
            | it must determine the initial position for each assigned partition.
            | When the group is first created, before any messages have been consumed, the position is set according to a configurable
            | offset reset policy (auto.offset.reset). Typically, consumption starts either at the earliest offset or the latest offset.
            | You can choose between "latest", "earliest" or "none".
            */
            'offset_reset' => 'latest',

            /*
             | If you set enable.auto.commit (which is the default), then the consumer will automatically commit offsets periodically at the
             | interval set by auto.commit.interval.ms.
             */
            'auto_commit' => true,
            'max_commit_retries' => 6,
            'commit_batch_size' => null,
            /*
             * The max number of messages that should be consumed.
             */
            'max_messages' => -1,
            'security_protocol' => 'plaintext',
            /*
             * Custom configuration options for this consumer.
             */
            'options' => [],
        ]
    ]
];
