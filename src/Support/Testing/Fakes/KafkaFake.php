<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Illuminate\Support\Collection;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Connection;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Factory;
use Override;
use PHPUnit\Framework\Assert as PHPUnit;

class KafkaFake extends Factory
{
    /** @var list<ProducerMessage> */
    private array $publishedMessages = [];

    /** @var list<ConsumerMessage> */
    private array $messagesToConsume = [];

    /** Set the messages to consume. */
    public function shouldReceiveMessages(ConsumerMessage|array $messages): void
    {
        if (! is_array($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $m) {
            $this->addConsumerMessage($m);
        }
    }

    /**
     * Assert that a message was published. It can be matched against an expected message, a callback
     * receiving each published message and returning whether it matches, or both.
     */
    public function assertPublished(ProducerMessage|callable|null $expected = null, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            condition: $this->published($expected, $callback)->isNotEmpty(),
            message: 'The expected message was not published.'
        );
    }

    /** Assert that a number of messages were published, optionally matching an expected message or a callback. */
    public function assertPublishedTimes(int $times = 1, ProducerMessage|callable|null $expected = null, ?callable $callback = null): void
    {
        $count = $this->published($expected, $callback)->count();

        PHPUnit::assertSame($times, $count, "Kafka published {$count} messages instead of {$times}.");
    }

    /** Assert that a message was published on a topic, optionally matching an expected message or a callback. */
    public function assertPublishedOn(string $topic, ProducerMessage|callable|null $expected = null, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            condition: $this->published($expected, $callback, $topic)->isNotEmpty(),
            message: "The expected message was not published on the [{$topic}] topic."
        );
    }

    /** Assert that a number of messages were published on a topic, optionally matching an expected message or a callback. */
    public function assertPublishedOnTimes(string $topic, int $times = 1, ProducerMessage|callable|null $expected = null, ?callable $callback = null): void
    {
        $count = $this->published($expected, $callback, $topic)->count();

        PHPUnit::assertSame($times, $count, "Kafka published {$count} messages on the [{$topic}] topic instead of {$times}.");
    }

    /** Assert that no message matching the expected message or the callback was published. */
    public function assertNotPublished(ProducerMessage|callable $expected, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            condition: $this->published($expected, $callback)->isEmpty(),
            message: 'The unexpected message was published.'
        );
    }

    /** Assert that no messages were published. */
    public function assertNothingPublished(): void
    {
        PHPUnit::assertEmpty($this->publishedMessages, 'Messages were published unexpectedly.');
    }

    /** Assert that no messages were published on a topic. */
    public function assertNothingPublishedOn(string $topic): void
    {
        $count = $this->published(null, null, $topic)->count();

        PHPUnit::assertSame(0, $count, "Kafka published {$count} messages on the [{$topic}] topic unexpectedly.");
    }

    /** Connections that are not configured are allowed, so tests don't need a Kafka configuration. */
    #[Override]
    protected function configuration(string $name): ConnectionConfig
    {
        $config = config("kafka.connections.{$name}");

        return ConnectionConfig::fromArray($name, [
            ...(is_array($config) ? $config : []),
            'brokers' => $config['brokers'] ?? 'localhost:9092',
        ]);
    }

    #[Override]
    protected function makeConnection(ConnectionConfig $config): Connection
    {
        return new FakeConnection(
            $config,
            fn (ProducerMessage $message) => $this->publishedMessages[] = $message,
            fn () => $this->messagesToConsume,
            $this->configureConsumer(...),
        );
    }

    /** Add a message to array of messages to be consumed. */
    private function addConsumerMessage(ConsumerMessage $message): void
    {
        $this->messagesToConsume[] = $message;
    }

    /**
     * Get the published messages matching the expected message and the callback. A callable
     * given as the expected message is used as the callback.
     */
    private function published(ProducerMessage|callable|null $expected, ?callable $callback, ?string $topic = null): Collection
    {
        if (is_callable($expected)) {
            [$expected, $callback] = [null, $expected];
        }

        return collect($this->publishedMessages)
            ->filter(fn (ProducerMessage $message) => $topic === null || $message->getTopicName() === $topic)
            ->filter(fn (ProducerMessage $message) => ! $expected instanceof ProducerMessage || $this->sameMessage($message, $expected))
            ->filter(fn (ProducerMessage $message) => $callback === null || $callback($message));
    }

    private function sameMessage(ProducerMessage $published, ProducerMessage $expected): bool
    {
        return json_encode($this->withoutMessageId($published->toArray()), JSON_THROW_ON_ERROR)
            === json_encode($this->withoutMessageId($expected->toArray()), JSON_THROW_ON_ERROR);
    }

    /** Messages are compared without their id, which is generated for every message. */
    private function withoutMessageId(array $message): array
    {
        unset($message['headers'][config('kafka.message_id_key')]);

        return $message;
    }
}
