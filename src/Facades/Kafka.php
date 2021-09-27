<?php

namespace Junges\Kafka\Facades;

use Illuminate\Support\Facades\Facade;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Support\Testing\Fakes\KafkaFake;

/**
 * @method static \Junges\Kafka\Contracts\CanProduceMessages publishOn(string $broker, string $topic);
 * @method static \Junges\Kafka\Consumers\ConsumerBuilder createConsumer(string $brokers, array $topics, string $groupId = null);
 * @method static void assertPublished(Message $message);
 * @method static void assertPublishedOn(string $topic, Message $message, $callback = null)
 * @method static void assertNothingPublished()
 * @mixin \Junges\Kafka\Kafka
 */
class Kafka extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Junges\Kafka\Support\Testing\Fakes\KafkaFake
     */
    public static function fake(): KafkaFake
    {
        static::swap($fake = new KafkaFake());

        return $fake;
    }

    public static function getFacadeAccessor(): string
    {
        return \Junges\Kafka\Kafka::class;
    }
}
