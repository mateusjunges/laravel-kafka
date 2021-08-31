<?php

return [
    'topic' => 'laravel-kafka-topic',
    'broker' => env('KAFKA_BROKERS'),
    'groupId' => 'laravel-kafka-test',
    'securityProtocol' => 'PLAINTEXT',
    'sasl' => [
        'username' => '',
        'password' => '',
        'mechanisms' => '',
    ]
];