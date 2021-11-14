<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use PHPUnit\Framework\Assert as PHPUnit;

class KafkaFake implements CanPublishMessagesToKafka
{
    private ProducerBuilderFake $producerBuilderFake;

    public function __construct()
    {
        $this->producerBuilderFake = new ProducerBuilderFake(
            topic: '',
            broker: ''
        );

        return $this->producerBuilderFake;
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
        $this->producerBuilderFake = new ProducerBuilderFake(
            topic: $topic,
            broker: $broker
        );

        return $this->producerBuilderFake;
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
        if ($message === null) {
            $this->assertPublished(null, function ($messageArray, $publishedTopic) use ($topic) {
                return $topic === $publishedTopic;
            });

            return;
        }

        $this->assertPublished($message, function ($messageArray, $publishedTopic) use ($callback, $topic, $message) {
            if ($publishedTopic !== $topic) {
                return false;
            }

            return $callback
                ? $callback($message, $messageArray)
                : true;
        });
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
        if ($message === null) {
            $this->assertPublishedTimes($times, null, function ($messageArray, $publishedTopic) use ($topic) {
                return $topic === $publishedTopic;
            });

            return;
        }

        $this->assertPublishedTimes($times, $message, function ($messageArray, $publishedTopic) use ($callback, $topic, $message) {
            if ($publishedTopic !== $topic) {
                return false;
            }

            return $callback
                ? $callback($message, $messageArray)
                : true;
        });
    }

    /**
     * Assert that no messages were published.
     */
    public function assertNothingPublished()
    {
        PHPUnit::assertEmpty($this->getPublishedMessages(), 'Messages were published unexpectedly.');
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

        $callback = $callback ?: function () {
            return true;
        };


        return collect($this->getPublishedMessages())->filter(function ($_, $topic) use ($message, $callback) {
            return $callback($message, $topic);
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
        return $this->producerBuilderFake->getProducer()->getPublishedMessages();
    }
}
