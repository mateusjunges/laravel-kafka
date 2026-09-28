<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Consumers\MessageHandler;
use Junges\Kafka\Contracts\Consumer as ConsumerContract;
use Junges\Kafka\Contracts\ConsumerBuilder as ConsumerBuilderContract;
use Override;

class BuilderFake extends Builder implements ConsumerBuilderContract
{
    /** @var \Junges\Kafka\Contracts\ConsumerMessage[] */
    private array $messages = [];

    /** {@inheritDoc} */
    #[Override]
    public static function create(ConnectionConfig $connection, array $topics = [], ?string $groupId = null): self
    {
        return new self(
            connection: $connection,
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
    #[Override]
    public function build(): ConsumerContract
    {
        $config = new Config(
            broker: $this->brokers,
            topics: $this->topics,
            securityProtocol: $this->getSecurityProtocol(),
            groupId: $this->groupId,
            handler: new MessageHandler($this->handler, $this->middlewares, $this->onMessageFailed),
            sasl: $this->saslConfig,
            dlq: $this->resolveDlq(),
            maxMessages: $this->maxMessages,
            autoCommit: $this->autoCommit,
            customOptions: $this->options,
            stopAfterLastMessage: $this->stopWhenEmpty,
            callbacks: $this->resolveCallbacks(),
            whenStopConsuming: $this->onStopConsuming,
            skipFailedMessages: $this->skipFailedMessages,
            failedMessageRetries: $this->failedMessageRetries,
            failedMessageRetryBackoff: $this->failedMessageRetryBackoff,
        );

        return new ConsumerFake(
            $config,
            $this->messages
        );
    }
}
