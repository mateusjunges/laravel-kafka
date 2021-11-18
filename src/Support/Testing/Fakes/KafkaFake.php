<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Message\Message;
use PHPUnit\Framework\Assert as PHPUnit;

class KafkaFake implements CanPublishMessagesToKafka
{
    private ProducerBuilderFake $producerBuilderFake;
    private array $publishedMessages = [];

    public function __construct()
    {
        $this->makeProducerBuilderFake();

        return $this->producerBuilderFake;
    }

    /**
     * Publish a message in the specified broker/topic.
     *
     * @param string $topic
     * @param string|null $broker
     * @return ProducerBuilderFake
     */
    public function publishOn(string $topic, ?string $broker = null): ProducerBuilderFake
    {
        return $this->makeProducerBuilderFake($topic, $broker);
    }

    /**
     * Assert if a messages was published based on a truth-test callback.
     *
     * @param KafkaProducerMessage|null $expectedMessage
     * @param callable|null $callback
     */
    public function assertPublished(?KafkaProducerMessage $expectedMessage = null, ?callable $callback = null)
    {
        PHPUnit::assertTrue(
            condition: $this->published(null, $expectedMessage, $callback)->count() > 0,
            message: "The expected message was not published."
        );
    }

    /**
     * Assert if a messages was published based on a truth-test callback.
     *
     * @param int $times
     * @param KafkaProducerMessage|null $expectedMessage
     * @param callable|null $callback
     */
    public function assertPublishedTimes(int $times = 1, ?KafkaProducerMessage $expectedMessage = null, ?callable $callback = null)
    {
        $count = $this->published(null, $expectedMessage, $callback)->count();

        PHPUnit::assertTrue(
            condition: $count === $times,
            message: "Kafka published {$count} messages instead of {$times}."
        );
    }

    /**
     * Assert that a message was published on a specific topic.
     *
     * @param string $topic
     * @param KafkaProducerMessage|null $expectedMessage
     * @param callable|null $callback
     */
    public function assertPublishedOn(string $topic, ?KafkaProducerMessage $expectedMessage = null, ?callable $callback = null)
    {
        PHPUnit::assertTrue(
            condition: $this->published($topic, $expectedMessage, $callback)->count() > 0,
            message: "The expected message was not published."
        );
    }

    /**
     * Assert that a message was published on a specific topic.
     *
     * @param string $topic
     * @param int $times
     * @param KafkaProducerMessage|null $expectedMessage
     * @param callable|null $callback
     */
    public function assertPublishedOnTimes(string $topic, int $times = 1, ?KafkaProducerMessage $expectedMessage = null, ?callable $callback = null)
    {
        $count = $this->published($topic, $expectedMessage, $callback)->count();

        PHPUnit::assertTrue(
            condition: $count === $times,
            message: "Kafka published {$count} messages instead of {$times}."
        );
    }

    /**
     * Assert that no messages were published.
     */
    public function assertNothingPublished()
    {
        PHPUnit::assertEmpty($this->getPublishedMessages(), 'Messages were published unexpectedly.');
    }

    private function makeProducerBuilderFake(string $topic = '', ?string $broker = null): ProducerBuilderFake
    {
        $this->producerBuilderFake = new ProducerBuilderFake(
            topic: $topic,
            broker: $broker
        );

        $this->producerBuilderFake->withProducerCallback(fn (Message $message) => $this->publishedMessages[] = $message);

        return $this->producerBuilderFake;
    }

    /**
     * Get all messages matching a truth-test callback.
     *
     * @param string|null $topic
     * @param KafkaProducerMessage|null $expectedMessage
     * @param callable|null $callback
     * @return \Illuminate\Support\Collection
     */
    private function published(?string $topic, ?KafkaProducerMessage $expectedMessage = null, ?callable $callback = null): Collection
    {
        if (! $this->hasPublished()) {
            return collect();
        }

        return collect($this->getPublishedMessages())->filter(function (Message $publishedMessage) use ($topic, $expectedMessage, $callback) {
            if ($topic !== null && $publishedMessage->getTopicName() !== $topic) {
                return false;
            }
            if ($callback !== null) {
                $callback($publishedMessage);
            }
            if ($expectedMessage !== null) {
                return json_encode($publishedMessage->toArray()) === json_encode($expectedMessage->toArray());
            }

            return true;
        });
    }

    /**
     * Check if the producer has published messages.
     *
     * @return bool
     */
    #[Pure]
    private function hasPublished(): bool
    {
        return ! empty($this->getPublishedMessages());
    }

    /**
     * Get published messages.
     *
     * @return array
     */
    #[Pure]
    private function getPublishedMessages(): array
    {
        return $this->publishedMessages;
    }
}
