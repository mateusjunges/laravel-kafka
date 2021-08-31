<?php

namespace Junges\Kafka\Tests\Integration;

use Junges\Kafka\Contracts\Consumer;
use RdKafka\Message;

class TestConsumer
{
    public const RESPONSE_OK = 'response_ok';
    public const RESPONSE_ERROR = 'response_error';

    public static $messages;
    public static $responses;
    private static $callCounter;

    public function __construct(array $responses = [])
    {
        self::$messages = [];
        self::$responses = $responses;
        self::$callCounter = 0;
    }

    public function __invoke(string $message): void
    {
        if (!empty(self::$responses)) {
            $responseDirective = self::$responses[self::$callCounter++];
            if ($responseDirective == self::RESPONSE_ERROR) {
                throw new \Exception('Error processing message');
            } elseif (is_a(\Throwable::class, $responseDirective)) {
                throw $responseDirective;
            }
        }

        self::$messages[] = $message;

        return;
    }
}