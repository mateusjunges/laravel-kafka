<?php

return [
    'topic' => 'php-kafka-consumer-topic',
    'broker' => env('KAFKA_BROKERS'),
    'groupId' => 'php-kafka-consumer-test',
    'securityProtocol' => 'PLAINTEXT',
    'sasl' => [
        'username' => '',
        'password' => '',
        'mechanisms' => '',
    ]
];