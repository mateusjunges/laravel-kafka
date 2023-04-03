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
    private $messages = [];

    /**
     * @inheritDoc
     * @return $this
     */
    public static function create(string $brokers, array $topics = [], string $groupId = null): \Junges\Kafka\Consumers\ConsumerBuilder
    {
        return new ConsumerBuilderFake(
            $brokers,
            $topics,
            $groupId
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
        $config = new Config($this->brokers, $this->topics, $this->getSecurityProtocol(), $this->commit, $this->groupId, new CallableConsumer($this->handler, $this->middlewares), $this->saslConfig, $this->dlq, $this->maxMessages, $this->maxCommitRetries, $this->autoCommit, $this->options, $this->getBatchConfig(), $this->stopAfterLastMessage, 1000, $this->callbacks);

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
            new CallableBatchConsumer($this->handler),
            new Timer(),
            app(\Junges\Kafka\BatchRepositories\InMemoryBatchRepository::class),
            $this->batchingEnabled,
            $this->batchSizeLimit,
            $this->batchReleaseInterval
        );
    }
}
