<?php declare(strict_types=1);

namespace Junges\Kafka\Facades;

use Illuminate\Support\Facades\Facade;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Support\Testing\Fakes\KafkaFake;

/**
 * @method static \Junges\Kafka\Contracts\MessageProducer publishOn(string $topic, string $broker = null)
 * @method static \Junges\Kafka\Consumers\ConsumerBuilder createConsumer(array $topics = [], string $groupId = null, string $brokers = null)
 * @method static void assertPublished(ProducerMessage $expectedMessage = null, callable $callback = null)
 * @method static void assertPublishedTimes(int $times = 1, ProducerMessage $expectedMessage = null, callable $callback = null)
 * @method static void assertPublishedOn(string $topic, ProducerMessage $expectedMessage = null, callable $callback = null)
 * @method static void assertPublishedOnTimes(string $topic, int $times = 1, ProducerMessage $expectedMessage = null, callable $callback = null)
 * @method static void assertNothingPublished()
 * @method static void shouldReceiveMessages(\Junges\Kafka\Contracts\ConsumerMessage|\Junges\Kafka\Contracts\ConsumerMessage[] $messages)
 * @mixin \Junges\Kafka\Kafka
 *
 * @see \Junges\Kafka\Kafka
 */
class Kafka extends Facade
{
    /** Replace the bound instance with a fake. */
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
