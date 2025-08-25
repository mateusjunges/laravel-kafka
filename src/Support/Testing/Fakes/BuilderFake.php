<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Consumers\CallableConsumer;
use Junges\Kafka\Contracts\ConsumerBuilder as ConsumerBuilderContract;
use Junges\Kafka\Contracts\MessageConsumer;

class BuilderFake extends Builder implements ConsumerBuilderContract
{
    /** @var \Junges\Kafka\Contracts\ConsumerMessage[] */
    private array $messages = [];

    /** {@inheritDoc} */
    public static function create(?string $brokers, array $topics = [], ?string $groupId = null): self
    {
        return new self(
            brokers: $brokers,
            topics: $topics,
            groupId: $groupId
        );
    }

    /** Set fake messages to the consumer.  */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    /** Build the Kafka consumer. */
    public function build(): MessageConsumer
    {
        $config = new Config(
            broker: $this->brokers,
            topics: $this->topics,
            securityProtocol: $this->getSecurityProtocol(),
            commit: $this->commit,
            groupId: $this->groupId,
            consumer: new CallableConsumer($this->handler, $this->middlewares),
            sasl: $this->saslConfig,
            dlq: $this->dlq,
            maxMessages: $this->maxMessages,
            maxCommitRetries: $this->maxCommitRetries,
            autoCommit: $this->autoCommit,
            customOptions: $this->options,
            stopAfterLastMessage: $this->stopAfterLastMessage,
            callbacks: $this->callbacks,
            whenStopConsuming: $this->onStopConsuming,
        );

        return new ConsumerFake(
            $config,
            $this->messages
        );
    }
}
