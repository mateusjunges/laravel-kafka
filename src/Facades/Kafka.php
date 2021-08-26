<?php

namespace Junges\Kafka\Facades;

use Illuminate\Support\Facades\Facade;
use Junges\Kafka\Message;
use Junges\Kafka\Producers\ProducerBuilder;
use Junges\Kafka\Support\Testing\Fakes\KafkaFake;

/**
 * @method static ProducerBuilder publishOn(string $broker, string $topic);
 * @method static void assertPublished(Message $message);
 * @method static void assertNothingPublished()
 * @method static void assertPublishedOn(string $topic, Message $message, $callback = null)
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
