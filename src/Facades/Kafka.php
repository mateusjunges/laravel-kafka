<?php

namespace Junges\Kafka\Facades;

use Illuminate\Support\Facades\Facade;
use Junges\Kafka\KafkaFake;
use Junges\Kafka\Message;
use Junges\Kafka\Producers\ProducerBuilder;

/**
 * @method static ProducerBuilder publishOn(string $broker, string $topic);
 * @method static void assertPublished(Message $message);
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
