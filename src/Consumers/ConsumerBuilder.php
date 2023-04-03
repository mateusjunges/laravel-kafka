<?php

namespace Junges\Kafka\Consumers;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Commit\Contracts\CommitterFactory;
use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\BatchConfig;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\NullBatchConfig;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\CanConsumeMessages;
use Junges\Kafka\Contracts\ConsumerBuilder as ConsumerBuilderContract;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Exceptions\KafkaConsumerException;
use Junges\Kafka\Support\Timer;

class ConsumerBuilder implements ConsumerBuilderContract
{
    use InteractsWithConfigCallbacks;

    /**
     * @var mixed[]
     */
    protected $topics;
    /**
     * @var int
     */
    protected $commit;
    /**
     * @var string|null
     */
    protected $groupId;
    /**
     * @var \Closure
     */
    protected $handler;
    /**
     * @var int
     */
    protected $maxMessages;
    /**
     * @var int
     */
    protected $maxCommitRetries;
    /**
     * @var string
     */
    protected $brokers;
    /**
     * @var mixed[]
     */
    protected $middlewares;
    /**
     * @var \Junges\Kafka\Config\Sasl|null
     */
    protected $saslConfig;
    /**
     * @var string|null
     */
    protected $dlq;
    /**
     * @var string
     */
    protected $securityProtocol;
    /**
     * @var bool
     */
    protected $autoCommit;
    /**
     * @var mixed[]
     */
    protected $options;
    /**
     * @var \Junges\Kafka\Contracts\MessageDeserializer
     */
    protected $deserializer;
    /**
     * @var \Junges\Kafka\Commit\Contracts\CommitterFactory|null
     */
    protected $committerFactory;
    /**
     * @var bool
     */
    protected $batchingEnabled = false;
    /**
     * @var int
     */
    protected $batchSizeLimit = 0;
    /**
     * @var int
     */
    protected $batchReleaseInterval = 0;
    /**
     * @var bool
     */
    protected $stopAfterLastMessage = false;

    /**
     * @param string $brokers
     * @param array $topics
     * @param string|null $groupId
     */
    protected function __construct(string $brokers, array $topics = [], string $groupId = null)
    {
        if (count($topics) > 0) {
            foreach ($topics as $topic) {
                $this->validateTopic($topic);
            }
        }

        $this->brokers = $brokers;
        $this->groupId = $groupId;
        $this->topics = array_unique($topics);

        $this->commit = 1;
        $this->handler = function () {
        };

        $this->maxMessages = -1;
        $this->maxCommitRetries = 6;
        $this->middlewares = [];
        $this->securityProtocol = 'PLAINTEXT';
        $this->autoCommit = false;
        $this->options = [];

        $this->deserializer = resolve(MessageDeserializer::class);
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public static function create(string $brokers, array $topics = [], string $groupId = null): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        return new ConsumerBuilder(
            $brokers,
            $topics,
            $groupId
        );
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function subscribe(...$topics): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        if (is_array($topics[0])) {
            $topics = $topics[0];
        }

        foreach ($topics as $topic) {
            $this->validateTopic($topic);

            if (! collect($this->topics)->contains($topic)) {
                $this->topics[] = $topic;
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withBrokers(?string $brokers): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->brokers = $brokers ?? config('kafka.brokers');

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withConsumerGroupId(?string $groupId): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withCommitBatchSize(int $size): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->commit = $size;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withHandler(callable $handler): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->handler = \Closure::fromCallable($handler);

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function usingDeserializer(MessageDeserializer $deserializer): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function usingCommitterFactory(CommitterFactory $committerFactory): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->committerFactory = $committerFactory;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withMaxMessages(int $maxMessages): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->maxMessages = $maxMessages;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withMaxCommitRetries(int $maxCommitRetries): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->maxCommitRetries = $maxCommitRetries;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withDlq(?string $dlqTopic = null): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        if (! isset($this->topics[0])) {
            throw KafkaConsumerException::dlqCanNotBeSetWithoutSubscribingToAnyTopics();
        }

        if (null === $dlqTopic) {
            $dlqTopic = $this->topics[0] . '-dlq';
        }

        $this->dlq = $dlqTopic;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withSasl(Sasl $saslConfig): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withMiddleware(callable $middleware): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withSecurityProtocol(string $securityProtocol): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->securityProtocol = $securityProtocol;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withAutoCommit(bool $autoCommit = true): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->autoCommit = $autoCommit;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withOptions(array $options): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        foreach ($options as $name => $value) {
            $this->withOption($name, $value);
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @param mixed $value
     * @return $this
     */
    public function withOption(string $name, $value): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function enableBatching(): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->batchingEnabled = true;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withBatchSizeLimit(int $batchSizeLimit): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->batchSizeLimit = $batchSizeLimit;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withBatchReleaseInterval(int $batchReleaseIntervalInMilliseconds): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->batchReleaseInterval = $batchReleaseIntervalInMilliseconds;

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function stopAfterLastMessage(bool $stopAfterLastMessage = true): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        $this->stopAfterLastMessage = $stopAfterLastMessage;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(): CanConsumeMessages
    {
        $config = new Config($this->brokers, $this->topics, $this->getSecurityProtocol(), $this->commit, $this->groupId, new CallableConsumer($this->handler, $this->middlewares), $this->saslConfig, $this->dlq, $this->maxMessages, $this->maxCommitRetries, $this->autoCommit, $this->options, $this->getBatchConfig(), $this->stopAfterLastMessage, 1000, $this->callbacks);

        return new Consumer($config, $this->deserializer, $this->committerFactory);
    }

    /**
     * Validates each topic before subscribing.
     *
     * @param mixed $topic
     * @return void
     */
    protected function validateTopic($topic): void
    {
        if (! is_string($topic)) {
            $type = ucfirst(gettype($topic));

            throw new InvalidArgumentException("The topic name should be a string value. [{$type}] given.");
        }
    }

    /**
     * Get security protocol depending on whether sasl is set or not.
     *
     * @return string
     */
    protected function getSecurityProtocol(): string
    {
        return $this->saslConfig !== null
            ? $this->saslConfig->getSecurityProtocol()
            : $this->securityProtocol;
    }

    /**
     * Returns batch config if batching is enabled
     * if batching is disabled then null config returned
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
            app(config('kafka.batch_repository')),
            $this->batchingEnabled,
            $this->batchSizeLimit,
            $this->batchReleaseInterval
        );
    }
}
