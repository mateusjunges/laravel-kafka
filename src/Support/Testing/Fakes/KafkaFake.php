<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;
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

    /** Assert if a messages was published based on a truth-test callback. */
    public function assertPublished(?ProducerMessage $expectedMessage = null, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            condition: $this->published($callback, $expectedMessage)->count() > 0,
            message: 'The expected message was not published.'
        );
    }

    /** Assert if a messages was published based on a truth-test callback. */
    public function assertPublishedTimes(int $times = 1, ?ProducerMessage $expectedMessage = null, ?callable $callback = null): void
    {
        $count = $this->published($callback, $expectedMessage)->count();

        PHPUnit::assertTrue(
            condition: $count === $times,
            message: "Kafka published {$count} messages instead of {$times}."
        );
    }

    /** Assert that a message was published on a specific topic. */
    public function assertPublishedOn(string $topic, ?ProducerMessage $expectedMessage = null, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            condition: $this->published($callback, $expectedMessage, $topic)->count() > 0,
            message: 'The expected message was not published.'
        );
    }

    /** Assert that a message was published on a specific topic. */
    public function assertPublishedOnTimes(string $topic, int $times = 1, ?ProducerMessage $expectedMessage = null, ?callable $callback = null): void
    {
        $count = $this->published($callback, $expectedMessage, $topic)->count();

        PHPUnit::assertSame(
            $count,
            $times,
            "Kafka published {$count} messages instead of {$times}."
        );
    }

    /** Assert that no messages were published. */
    public function assertNothingPublished(): void
    {
        PHPUnit::assertEmpty($this->getPublishedMessages(), 'Messages were published unexpectedly.');
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
        );
    }

    /** Add a message to array of messages to be consumed. */
    private function addConsumerMessage(ConsumerMessage $message): void
    {
        $this->messagesToConsume[] = $message;
    }

    /*** Get all messages matching a truth-test callback. */
    private function published(?callable $callback = null, ?ProducerMessage $expectedMessage = null, ?string $topic = null): Collection
    {
        if (! $this->hasPublished()) {
            return collect();
        }

        return collect($this->getPublishedMessages())
            ->filter(function (ProducerMessage $publishedMessage) use ($topic, $expectedMessage, $callback) {
                if ($topic !== null && $publishedMessage->getTopicName() !== $topic) {
                    return false;
                }

                if ($callback !== null) {
                    return $callback($publishedMessage);
                }

                if ($expectedMessage !== null) {
                    return json_encode($publishedMessage->toArray(), JSON_THROW_ON_ERROR) === json_encode($expectedMessage->toArray(), JSON_THROW_ON_ERROR);
                }

                return true;
            });
    }

    /** Check if the producer has published messages. */
    #[Pure]
    private function hasPublished(): bool
    {
        return ! empty($this->getPublishedMessages());
    }

    /** Get published messages. */
    #[Pure]
    private function getPublishedMessages(): array
    {
        return $this->publishedMessages;
    }
}
