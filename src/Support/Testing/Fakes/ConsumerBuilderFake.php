<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Support\Timer;
use Junges\Kafka\Config\BatchConfig;
use Junges\Kafka\Config\NullBatchConfig;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Consumers\CallableConsumer;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Commit\Contracts\CommitterFactory;
use Junges\Kafka\Exceptions\KafkaConsumerException;
use Junges\Kafka\Support\Testing\Fakes\ConsumerFake;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;

class ConsumerBuilderFake extends ConsumerBuilder
{
    /** @var \Junges\Kafka\Contracts\KafkaConsumerMessage[] */
    private array $messages = [];

    /**
     * Creates a new ConsumerBuilder instance.
     *
     * @param string $brokers
     * @param array $topics
     * @param string|null $groupId
     * @return static
     */
    public static function create(string $brokers, array $topics = [], string $groupId = null): self
    {
        return new ConsumerBuilderFake(
            brokers: $brokers,
            topics: $topics,
            groupId: $groupId
        );
    }

    /**
     * Set fake messages to the consumer.
     *
     * @param \Junges\Kafka\Contracts\KafkaConsumerMessage[] $messages
     * @return $this
     */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Build the Kafka consumer.
     *
     * @return \Junges\Kafka\Support\Testing\Fakes\ConsumerFake
     */
    public function build()
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
            batchConfig: $this->getBatchConfig(),
        );

        return new ConsumerFake(
            $config,
            $this->messages
        );
    }
}
