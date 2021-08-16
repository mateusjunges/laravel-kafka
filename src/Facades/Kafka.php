<?php

namespace Junges\Kafka\Facades;

use Illuminate\Support\Facades\Facade;
use Junges\Kafka\KafkaFake;
use Junges\Kafka\Message;

/**
 * @method static bool publish(Message $message, string $topic, $key = null)
 */
class Kafka extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return \Junges\Kafka\Kafka::class;
    }

    public static function fake(): KafkaFake
    {
        static::swap($fake = new KafkaFake());

        return $fake;
    }
}