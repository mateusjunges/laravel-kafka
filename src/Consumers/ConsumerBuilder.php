<?php

namespace Junges\Kafka\Consumers;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Commit\Contracts\CommitterFactory;
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
    protected array $topics;
    protected int $commit;
    protected ?string $groupId;
    protected Closure $handler;
    protected int $maxMessages;
    protected int $maxCommitRetries;
    protected string $brokers;
    protected array $middlewares;
    protected ?Sasl $saslConfig = null;
    protected ?string $dlq = null;
    protected string $securityProtocol;
    protected bool $autoCommit;
    protected array $options;
    protected MessageDeserializer $deserializer;
    protected ?CommitterFactory $committerFactory = null;
    protected bool $batchingEnabled = false;
    protected int $batchSizeLimit = 0;
    protected int $batchReleaseInterval = 0;

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
     */
    public static function create(string $brokers, array $topics = [], string $groupId = null): self
    {
        return new ConsumerBuilder(
            brokers: $brokers,
            topics: $topics,
            groupId: $groupId
        );
    }

    /**
     * @inheritDoc
     */
    public function subscribe(...$topics): self
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
     */
    public function withBrokers(?string $brokers): self
    {
        $this->brokers = $brokers ?? config('kafka.brokers');

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withConsumerGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withCommitBatchSize(int $size): self
    {
        $this->commit = $size;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withHandler(callable $handler): self
    {
        $this->handler = Closure::fromCallable($handler);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function usingDeserializer(MessageDeserializer $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function usingCommitterFactory(CommitterFactory $committerFactory): self
    {
        $this->committerFactory = $committerFactory;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withMaxMessages(int $maxMessages): self
    {
        $this->maxMessages = $maxMessages;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withMaxCommitRetries(int $maxCommitRetries): self
    {
        $this->maxCommitRetries = $maxCommitRetries;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withDlq(?string $dlqTopic = null): self
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
     */
    public function withSasl(Sasl $saslConfig): self
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withMiddleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withSecurityProtocol(string $securityProtocol): self
    {
        $this->securityProtocol = $securityProtocol;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withAutoCommit(bool $autoCommit = true): self
    {
        $this->autoCommit = $autoCommit;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->withOption($name, $value);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withOption(string $name, string $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function enableBatching(): self
    {
        $this->batchingEnabled = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withBatchSizeLimit(int $batchSizeLimit): self
    {
        $this->batchSizeLimit = $batchSizeLimit;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withBatchReleaseInterval(int $batchReleaseIntervalInMilliseconds): self
    {
        $this->batchReleaseInterval = $batchReleaseIntervalInMilliseconds;

        return $this;
    }

    /**
     * @inheritDoc
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
        );

        return new Consumer($config, $this->deserializer, $this->committerFactory);
    }

    /**
     * Validates each topic before subscribing.
     *
     * @param mixed $topic
     * @return void
     */
    protected function validateTopic(mixed $topic): void
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
            batchConsumer: new CallableBatchConsumer($this->handler),
            timer: new Timer(),
            batchRepository: app(config('kafka.batch_repository')),
            batchingEnabled: $this->batchingEnabled,
            batchSizeLimit: $this->batchSizeLimit,
            batchReleaseInterval: $this->batchReleaseInterval
        );
    }
}
