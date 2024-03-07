<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Manager;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Message\Message;
use PHPUnit\Framework\Assert as PHPUnit;

class KafkaFake
{
    use ForwardsCalls;

    private Manager $kafkaManager;

    private array $publishedMessages = [];

    /** @var \Junges\Kafka\Contracts\ConsumerMessage[] */
    private array $messagesToConsume = [];

    public function __construct(?Manager $manager)
    {
        $this->kafkaManager = $manager?->shouldFake();
        $this->makeProducerBuilderFake();
    }

    /** Publish a message in the specified broker/topic. */
    public function publish(?string $broker = null): ProducerBuilderFake
    {
        return $this->makeProducerBuilderFake($broker);
    }

    /** Return a ConsumerBuilder instance. */
    public function consumer(array $topics = [], string $groupId = null, string $brokers = null): BuilderFake
    {
        return BuilderFake::create(
            brokers: $brokers ?? config('kafka.brokers'),
            topics: $topics,
            groupId: $groupId ?? config('kafka.consumer_group_id')
        )->setMessages(
            $this->messagesToConsume
        );
    }

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

    /** Add a message to array of messages to be consumed. */
    private function addConsumerMessage(ConsumerMessage $message): void
    {
        $this->messagesToConsume[] = $message;
    }

    /** Assert if a messages was published based on a truth-test callback. */
    public function assertPublished(?ProducerMessage $expectedMessage = null, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            condition: $this->published($callback, $expectedMessage)->count() > 0,
            message: "The expected message was not published."
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
            message: "The expected message was not published."
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

    private function makeProducerBuilderFake(?string $broker = null): ProducerBuilderFake
    {
        return (new ProducerBuilderFake(broker: $broker))
            ->withProducerCallback(
                fn (Message $message) => $this->publishedMessages[] = $message
            );
    }

    /*** Get all messages matching a truth-test callback. */
    private function published(?callable $callback = null, ?ProducerMessage $expectedMessage = null, ?string $topic = null): Collection
    {
        if (! $this->hasPublished()) {
            return collect();
        }

        return collect($this->getPublishedMessages())
            ->filter(function (Message $publishedMessage) use ($topic, $expectedMessage, $callback) {
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

    /**
     * Handle dynamic method calls to the kafka manager.
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        $this->kafkaManager->shouldReceiveMessages($this->messagesToConsume);

        return $this->forwardCallTo($this->kafkaManager, $method, $parameters);
    }
}
