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
        return $this->makeProducerBuilderFake();
    }

    /**
     * Publish a message in the specified broker/topic.
     *
     * @param string $topic
     * @param string|null $broker
     * @return ProducerBuilderFake
     */
    public function publishOn(string $topic, string $broker = null): ProducerBuilderFake
    {
        return $this->makeProducerBuilderFake($topic, $broker);
    }

    /**
     * Assert if a messages was published based on a truth-test callback.
     *
     * @param KafkaProducerMessage|null $message
     * @param null $callback
     */
    public function assertPublished(KafkaProducerMessage $message = null, $callback = null)
    {
        PHPUnit::assertTrue(
            condition: $this->published($message, $callback)->count() > 0,
            message: "The expected message was not published."
        );
    }

    /**
     * Assert if a messages was published based on a truth-test callback.
     *
     * @param KafkaProducerMessage|null $message
     * @param null $callback
     */
    public function assertPublishedTimes(int $times = 1, KafkaProducerMessage $message = null, $callback = null)
    {
        $count = $this->published($message, $callback)->count();

        PHPUnit::assertTrue(
            condition: $count === $times,
            message: "Kafka published {$count} messages instead of {$times}."
        );
    }

    /**
     * Assert that a message was published on a specific topic.
     *
     * @param string $topic
     * @param KafkaProducerMessage|null $message
     * @param callable|null $callback
     */
    public function assertPublishedOn(string $topic, KafkaProducerMessage $message = null, callable $callback = null)
    {
        PHPUnit::assertTrue(
            condition: $this->published($message, $callback)->count() > 0,
            message: "The expected message was not published."
        );
    }

    /**
     * Assert that a message was published on a specific topic.
     *
     * @param string $topic
     * @param int $times
     * @param KafkaProducerMessage|null $message
     * @param callable|null $callback
     */
    public function assertPublishedOnTimes(string $topic, int $times = 1, KafkaProducerMessage $message = null, callable $callback = null)
    {
        $count = $this->published($message, $callback)->count();

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

        $this->producerBuilderFake->withProduceCallback(fn (Message $message) => $this->publishedMessages[] = $message);

        return $this->producerBuilderFake;
    }

    /**
     * Get all messages matching a truth-test callback.
     *
     * @param KafkaProducerMessage|null $message
     * @param null $callback
     * @return \Illuminate\Support\Collection
     */
    private function published(KafkaProducerMessage $message = null, $callback = null): Collection
    {
        if (! $this->hasPublished()) {
            return collect();
        }

        return collect($this->getPublishedMessages())->filter(function (Message $publishedMessage) use ($message, $callback) {
            if ($callback !== null) {
                $callback($publishedMessage);
            }

            if ($message !== null) {
                return json_encode($publishedMessage->toArray()) === json_encode($message->toArray());
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
