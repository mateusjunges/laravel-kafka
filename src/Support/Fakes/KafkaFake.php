<?php

namespace Junges\Kafka\Support\Fakes;

use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Message;
use PHPUnit\Framework\Assert as PHPUnit;
use function collect;

class KafkaFake implements CanPublishMessagesToKafka
{
    private ProducerBuilderFake $producerBuilderFake;

    public function __construct()
    {
        $this->producerBuilderFake = new ProducerBuilderFake(
            broker: '',
            topic: ''
        );

        return $this->producerBuilderFake;
    }

    /**
     * Publish a message in the specified broker/topic.
     *
     * @param string $broker
     * @param string $topic
     * @return ProducerBuilderFake
     */
    public function publishOn(string $broker, string $topic): ProducerBuilderFake
    {
        $this->producerBuilderFake = new ProducerBuilderFake(
            broker: $broker,
            topic: $topic
        );

        return $this->producerBuilderFake;
    }

    /**
     * Assert if a messages was published based on a truth-test callback.
     *
     * @param Message $message
     * @param null $callback
     */
    public function assertPublished(Message $message, $callback = null)
    {
        PHPUnit::assertTrue(
            condition: $this->published($message, $callback)->count() > 0,
            message: "The expected message was not published."
        );
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
     * @param $message
     * @param null $callback
     * @return \Illuminate\Support\Collection
     */
    private function published($message, $callback = null): Collection
    {
        if (! $this->hasPublished($message)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };


        return collect($this->getPublishedMessages())->filter(function ($message, $topic) use ($callback) {
            return $callback($message, $topic);
        });
    }

    /**
     * Check if the producer has published messages.
     *
     * @param $message
     * @return bool
     */
    private function hasPublished($message): bool
    {
        return ! empty($this->getPublishedMessages());
    }

    /**
     * Assert that a message was published on a specific topic.
     *
     * @param string $topic
     * @param Message $message
     * @param null $callback
     */
    public function assertPublishedOn(string $topic, Message $message, $callback = null)
    {
        $this->assertPublished($message, function ($message, $publishedTopic) use ($callback, $topic) {
            if ($publishedTopic !== $topic) {
                return false;
            }

            return $callback ? $callback(...func_get_args()) : true;
        });
    }

    /**
     * Get published messages.
     *
     * @return array
     */
    private function getPublishedMessages(): array
    {
        return $this->producerBuilderFake->getProducer()->getPublishedMessages();
    }
}
