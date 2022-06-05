<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Message\Message;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Support\Testing\Fakes\ConsumerBuilderFake;

class KafkaFake implements CanPublishMessagesToKafka
{
    private array $publishedMessages = [];
    /** @var \Junges\Kafka\Contracts\KafkaConsumerMessage[] */
    private array $messagesToConsume = [];

    public function __construct()
    {
        $this->makeProducerBuilderFake();
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
     * Return a ConsumerBuilder instance.
     *
     * @param array $topics
     * @param string|null $groupId
     * @param string|null $brokers
     * @return \Junges\Kafka\Support\Testing\Fakes\ConsumerBuilderFake
     */
    public function createConsumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilderFake
    {
        return ConsumerBuilderFake::create(
            brokers: $brokers ?? config('kafka.brokers'),
            topics: $topics,
            groupId: $groupId ?? config('kafka.consumer_group_id')
        )->setMessages(
            $this->messagesToConsume
        );
    }

    /**
     * Add a mock messages to consume.
     *
     * @param \Junges\Kafka\Contracts\KafkaConsumerMessage|Junges\Kafka\Contracts\KafkaConsumerMessage[] $messages
     * @return void
     */
    public function addMockConsumerMessages(KafkaConsumerMessage|array $messages): void
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $m) {
            $this->addConsumerMessage($m);
        }
    }

    /**
     * Add a message to array of messages to be consumed.
     *
     * @param \Junges\Kafka\Contracts\KafkaConsumerMessage $message
     * @return void
     */
    private function addConsumerMessage(KafkaConsumerMessage $message): void
    {
        $this->messagesToConsume[] = $message;
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
            condition: $this->published($callback, $expectedMessage)->count() > 0,
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
        $count = $this->published($callback, $expectedMessage)->count();

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
            condition: $this->published($callback, $expectedMessage, $topic)->count() > 0,
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
        $count = $this->published($callback, $expectedMessage, $topic)->count();

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
        return (new ProducerBuilderFake(
                topic: $topic,
                broker: $broker
            )
        )->withProducerCallback(fn (Message $message) => $this->publishedMessages[] = $message);
    }

    /**
     * Get all messages matching a truth-test callback.
     *
     * @param string|null $topic
     * @param KafkaProducerMessage|null $expectedMessage
     * @param callable|null $callback
     * @return \Illuminate\Support\Collection
     */
    private function published(?callable $callback = null, ?KafkaProducerMessage $expectedMessage = null, ?string $topic = null): Collection
    {
        if (!$this->hasPublished()) {
            return collect();
        }

        return collect($this->getPublishedMessages())->filter(function (Message $publishedMessage) use ($topic, $expectedMessage, $callback) {
            if ($topic !== null && $publishedMessage->getTopicName() !== $topic) {
                return false;
            }
            if ($callback !== null) {
                return $callback($publishedMessage);
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
        return !empty($this->getPublishedMessages());
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
