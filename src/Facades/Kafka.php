<?php declare(strict_types=1);

namespace Junges\Kafka\Facades;

use Illuminate\Support\Facades\Facade;
use Junges\Kafka\Factory;
use Junges\Kafka\Support\Testing\Fakes\KafkaFake;

/**
 * @method static \Junges\Kafka\Connection connection(string|null $name = null)
 * @method static \Junges\Kafka\Producers\PendingMessage publish(string|null $topic = null)
 * @method static \Junges\Kafka\Producers\PendingMessage publishSync(string|null $topic = null)
 * @method static \Junges\Kafka\Consumers\Builder consumer(array $topics = [], string|null $groupId = null)
 * @method static \Junges\Kafka\Consumers\Builder consumerFor(\Junges\Kafka\KafkaConsumer|string $consumer)
 * @method static void consumerMiddleware(array|\Junges\Kafka\Contracts\Middleware|\Closure|string $middleware)
 * @method static array getConsumerMiddleware()
 * @method static void flush()
 * @method static string getDefaultConnection()
 * @method static void assertPublished(\Junges\Kafka\Contracts\ProducerMessage|null $expectedMessage = null, callable|null $callback = null)
 * @method static void assertPublishedTimes(int $times = 1, \Junges\Kafka\Contracts\ProducerMessage|null $expectedMessage = null, callable|null $callback = null)
 * @method static void assertPublishedOn(string $topic, \Junges\Kafka\Contracts\ProducerMessage|null $expectedMessage = null, callable|null $callback = null)
 * @method static void assertPublishedOnTimes(string $topic, int $times = 1, \Junges\Kafka\Contracts\ProducerMessage|null $expectedMessage = null, callable|null $callback = null)
 * @method static void assertNothingPublished()
 * @method static void shouldReceiveMessages(\Junges\Kafka\Contracts\ConsumerMessage|\Junges\Kafka\Contracts\ConsumerMessage[] $messages)
 *
 * @see Factory
 * @see KafkaFake
 */
class Kafka extends Facade
{
    /** Replace the bound instance with a fake. */
    public static function fake(): KafkaFake
    {
        $manager = static::getFacadeRoot();

        static::swap($fake = new KafkaFake);

        // Global middlewares are usually registered by a service provider, so the fake keeps them.
        if ($manager instanceof Factory) {
            $fake->consumerMiddleware($manager->getConsumerMiddleware());
        }

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return Factory::class;
    }
}
