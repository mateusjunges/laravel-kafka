<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\BatchConfig;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\NullBatchConfig;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\CommitterFactory;
use Junges\Kafka\Contracts\ConsumerBuilder as ConsumerBuilderContract;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\Middleware;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Support\Timer;
use RdKafka\TopicPartition;

class Builder implements ConsumerBuilderContract
{
    use InteractsWithConfigCallbacks;
    use Conditionable;

    /** @var list<string> */
    protected array $topics;
    protected int $commit;
    protected Closure | Handler $handler;
    protected int $maxMessages;
    protected int $maxTime = 0;
    protected int $maxCommitRetries;

    /** @var list<callable> */
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
    protected bool $stopAfterLastMessage = false;

    /** @var list<callable> */
    protected array $beforeConsumingCallbacks = [];

    /** @var list<callable> */
    protected array $afterConsumingCallbacks = [];

    /** @var array<int, TopicPartition> */
    protected array $partitionAssignment = [];

    protected ?Closure $onStopConsuming = null;

    protected function __construct(protected string $brokers, array $topics = [], protected ?string $groupId = null)
    {
        if (count($topics) > 0) {
            foreach ($topics as $topic) {
                $this->validateTopic($topic);
            }
        }
        $this->topics = array_unique($topics);

        $this->commit = 1;
        $this->handler = function () {
        };

        $this->maxMessages = -1;
        $this->maxCommitRetries = 6;
        $this->middlewares = [];
        $this->securityProtocol = 'PLAINTEXT';
        $this->autoCommit = config('kafka.auto_commit');
        $this->options = [];

        $this->deserializer = app(MessageDeserializer::class);
    }

    /** @inheritDoc */
    public static function create(string $brokers, array $topics = [], string $groupId = null): self
    {
        return new Builder(
            brokers: $brokers,
            topics: $topics,
            groupId: $groupId
        );
    }

    /** @inheritDoc */
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

    /** @inheritDoc */
    public function withBrokers(?string $brokers): self
    {
        $this->brokers = $brokers ?? config('kafka.brokers');

        return $this;
    }

    /** @inheritDoc */
    public function withConsumerGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /** @inheritDoc */
    public function withCommitBatchSize(int $size): self
    {
        $this->commit = $size;

        return $this;
    }

    /** @inheritDoc */
    public function withHandler(callable|Handler $handler): self
    {
        $this->handler = $handler instanceof Handler
            ? $handler
            : $handler(...);

        return $this;
    }

    /** @inheritDoc */
    public function usingDeserializer(MessageDeserializer $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /** @inheritDoc */
    public function usingCommitterFactory(CommitterFactory $committerFactory): self
    {
        $this->committerFactory = $committerFactory;

        return $this;
    }

    /** @inheritDoc */
    public function withMaxMessages(int $maxMessages): self
    {
        $this->maxMessages = $maxMessages;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withMaxTime(int $maxTime): self
    {
        $this->maxTime = $maxTime;

        return $this;
    }

    /** @inheritDoc */
    public function withMaxCommitRetries(int $maxCommitRetries): self
    {
        $this->maxCommitRetries = $maxCommitRetries;

        return $this;
    }

    /** @inheritDoc */
    public function withDlq(?string $dlqTopic = null): self
    {
        if (! isset($this->topics[0])) {
            throw ConsumerException::dlqCanNotBeSetWithoutSubscribingToAnyTopics();
        }

        if (null === $dlqTopic) {
            $dlqTopic = $this->topics[0] . '-dlq';
        }

        $this->dlq = $dlqTopic;

        return $this;
    }

    /** Set Sasl configuration. */
    public function withSasl(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT'): self
    {
        $this->saslConfig = new Sasl(
            username: $username,
            password: $password,
            mechanisms: $mechanisms,
            securityProtocol: $securityProtocol
        );

        return $this;
    }

    /** @inheritDoc */
    public function withMiddleware(Middleware|callable|string $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /** @inheritDoc */
    public function withSecurityProtocol(string $securityProtocol): self
    {
        $this->securityProtocol = $securityProtocol;

        return $this;
    }

    /** @inheritDoc */
    public function withAutoCommit(bool $autoCommit = true): self
    {
        $this->autoCommit = $autoCommit;

        return $this;
    }

    /** @inheritDoc */
    public function withOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->withOption($name, $value);
        }

        return $this;
    }

    /** @inheritDoc */
    public function withOption(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /** @inheritDoc */
    public function enableBatching(): self
    {
        $this->batchingEnabled = true;

        return $this;
    }

    /** @inheritDoc */
    public function withBatchSizeLimit(int $batchSizeLimit): self
    {
        $this->batchSizeLimit = $batchSizeLimit;

        return $this;
    }

    /** @inheritDoc */
    public function withBatchReleaseInterval(int $batchReleaseIntervalInMilliseconds): self
    {
        $this->batchReleaseInterval = $batchReleaseIntervalInMilliseconds;

        return $this;
    }

    /** @inheritDoc */
    public function stopAfterLastMessage(bool $stopAfterLastMessage = true): self
    {
        $this->stopAfterLastMessage = $stopAfterLastMessage;

        return $this;
    }

    public function beforeConsuming(callable $callable): self
    {
        $this->beforeConsumingCallbacks[] = $callable(...);

        return $this;
    }

    public function afterConsuming(callable $callable): self
    {
        $this->afterConsumingCallbacks[] = $callable(...);

        return $this;
    }

    public function assignPartitions(array $partitionAssignment): self
    {
        foreach ($partitionAssignment as $assigment) {
            if (! $assigment instanceof TopicPartition) {
                throw new InvalidArgumentException('The partition assignment must be an instance of [\RdKafka\TopicPartition]');
            }
        }

        $this->partitionAssignment = $partitionAssignment;

        return $this;
    }

    public function onStopConsuming(callable $onStopConsuming): self
    {
        $this->onStopConsuming = $onStopConsuming(...);

        return $this;
    }

    /** @inheritDoc */
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
            batchConfig: $this->getBatchConfig(),
            stopAfterLastMessage: $this->stopAfterLastMessage,
            callbacks: $this->callbacks,
            beforeConsumingCallbacks: $this->beforeConsumingCallbacks,
            afterConsumingCallbacks: $this->afterConsumingCallbacks,
            maxTime: $this->maxTime,
            partitionAssignment: $this->partitionAssignment,
            whenStopConsuming: $this->onStopConsuming,
        );

        return new Consumer($config, $this->deserializer, $this->committerFactory);
    }

    /** Validates each topic before subscribing. */
    protected function validateTopic(mixed $topic): void
    {
        if (! is_string($topic)) {
            $type = ucfirst(gettype($topic));

            throw new InvalidArgumentException("The topic name should be a string value. [{$type}] given.");
        }
    }

    /** Get security protocol depending on whether sasl is set or not. */
    protected function getSecurityProtocol(): string
    {
        return $this->saslConfig !== null
            ? $this->saslConfig->getSecurityProtocol()
            : $this->securityProtocol;
    }

    /**
     * Returns batch config if batching is enabled
     * if batching is disabled then null config returned
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
