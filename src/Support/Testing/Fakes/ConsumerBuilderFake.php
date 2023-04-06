<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Junges\Kafka\Config\BatchConfig;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\NullBatchConfig;
use Junges\Kafka\Consumers\CallableBatchConsumer;
use Junges\Kafka\Consumers\CallableConsumer;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\CanConsumeMessages;
use Junges\Kafka\Contracts\ConsumerBuilder as ConsumerBuilderContract;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Support\Timer;

class ConsumerBuilderFake extends ConsumerBuilder implements ConsumerBuilderContract
{
    /** @var \Junges\Kafka\Contracts\KafkaConsumerMessage[] */
    private array $messages = [];

    /** @inheritDoc */
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
     * @return \Junges\Kafka\Contracts\CanConsumeMessages
     */
    public function build(): CanConsumeMessages
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
            stopAfterLastMessage: $this->stopAfterLastMessage,
            callbacks: $this->callbacks,
        );

        return new ConsumerFake(
            $config,
            $this->messages
        );
    }

    /**
     * Returns a instance of BatchConfig if batching is enabled.
     * Otherwise, a instance of NullConfig will be returned.
     *
     * @return HandlesBatchConfiguration
     */
    protected function getBatchConfig(): HandlesBatchConfiguration
    {
        if (! $this->batchingEnabled) {
            return new NullBatchConfig();
        }

        return new BatchConfig(
            batchConsumer: new CallableBatchConsumer($this->handler),
            timer: new Timer(),
            batchRepository: app(\Junges\Kafka\BatchRepositories\InMemoryBatchRepository::class),
            batchingEnabled: $this->batchingEnabled,
            batchSizeLimit: $this->batchSizeLimit,
            batchReleaseInterval: $this->batchReleaseInterval
        );
    }
}
