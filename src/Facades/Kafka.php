<?php

namespace Junges\Kafka\Facades;

use Illuminate\Support\Facades\Facade;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Support\Testing\Fakes\KafkaFake;

/**
 * @method static \Junges\Kafka\Contracts\CanProduceMessages publishOn(string $topic, string $broker = null)
 * @method static \Junges\Kafka\Consumers\ConsumerBuilder createConsumer(array $topics = [], string $groupId = null, string $brokers = null)
 * @method static void assertPublished(KafkaProducerMessage $expectedMessage = null, callable $callback = null)
 * @method static void assertPublishedTimes(int $times = 1, KafkaProducerMessage $expectedMessage = null, callable $callback = null)
 * @method static void assertPublishedOn(string $topic, KafkaProducerMessage $expectedMessage = null, callable $callback = null)
 * @method static void assertPublishedOnTimes(string $topic, int $times = 1, KafkaProducerMessage $expectedMessage = null, callable $callback = null)
 * @method static void assertNothingPublished()
 * @method static \Junges\Kafka\Contracts\KafkaConsumerMessage addMockConsumerMessages(\Junges\Kafka\Contracts\KafkaConsumerMessage|\Junges\Kafka\Contracts\KafkaConsumerMessage[] $messages)
 * @mixin \Junges\Kafka\Kafka
 *
 * @see \Junges\Kafka\Kafka
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
