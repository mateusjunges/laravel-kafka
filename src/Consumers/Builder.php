<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Config\RebalanceStrategy;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\CommitterFactory;
use Junges\Kafka\Contracts\ConsumerBuilder as ConsumerBuilderContract;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\Middleware;
use Junges\Kafka\Exceptions\ConsumerException;
use RdKafka\TopicPartition;

class Builder implements ConsumerBuilderContract
{
    use Conditionable;
    use InteractsWithConfigCallbacks;

    /** @var list<string> */
    protected array $topics;

    protected Closure|Handler $handler;

    protected int $maxMessages;

    protected int $maxTime = 0;

    /** @var list<callable> */
    protected array $middlewares;

    protected ?Sasl $saslConfig = null;

    protected ?string $dlq = null;

    protected string $securityProtocol;

    protected bool $autoCommit;

    protected array $options;

    protected MessageDeserializer $deserializer;

    protected ?CommitterFactory $committerFactory = null;

    protected bool $stopAfterLastMessage = false;

    protected bool $skipFailedMessages = false;

    protected int $failedMessageRetries = 0;

    protected int $failedMessageRetryBackoff = 0;

    /** @var list<callable> */
    protected array $beforeConsumingCallbacks = [];

    /** @var list<callable> */
    protected array $afterConsumingCallbacks = [];

    /** @var array<int, TopicPartition> */
    protected array $partitionAssignment = [];

    protected ?Closure $onStopConsuming = null;

    protected ?Closure $partitionAssignmentCallback = null;

    protected string $brokers;

    protected ?string $groupId;

    protected int $consumerTimeoutInMs;

    protected function __construct(ConnectionConfig $connection, array $topics = [], ?string $groupId = null)
    {
        foreach ($topics as $topic) {
            $this->validateTopic($topic);
        }

        $this->topics = array_values(array_unique($topics));

        $this->brokers = $connection->brokers;
        $this->groupId = $groupId ?? $connection->groupId;
        $this->securityProtocol = $connection->securityProtocol ?? 'PLAINTEXT';
        $this->saslConfig = $connection->sasl;
        $this->autoCommit = $connection->autoCommit;
        $this->options = [...$connection->options, ...$connection->consumerOptions];
        $this->callbacks = $connection->callbacks;
        $this->consumerTimeoutInMs = $connection->consumerTimeoutInMs;

        $this->handler = function () {};
        $this->maxMessages = -1;
        $this->middlewares = [];

        $this->deserializer = app(MessageDeserializer::class);
    }

    /** {@inheritDoc} */
    public static function create(ConnectionConfig $connection, array $topics = [], ?string $groupId = null): self
    {
        return new self(
            connection: $connection,
            topics: $topics,
            groupId: $groupId
        );
    }

    /** {@inheritDoc} */
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

    /** {@inheritDoc} */
    public function withBrokers(string $brokers): self
    {
        $this->brokers = $brokers;

        return $this;
    }

    /** {@inheritDoc} */
    public function withConsumerGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /** {@inheritDoc} */
    public function withHandler(callable|Handler $handler): self
    {
        $this->handler = $handler instanceof Handler
            ? $handler
            : $handler(...);

        return $this;
    }

    /** {@inheritDoc} */
    public function usingDeserializer(MessageDeserializer $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /** {@inheritDoc} */
    public function usingCommitterFactory(CommitterFactory $committerFactory): self
    {
        $this->committerFactory = $committerFactory;

        return $this;
    }

    /** {@inheritDoc} */
    public function withMaxMessages(int $maxMessages): self
    {
        $this->maxMessages = $maxMessages;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withMaxTime(int $maxTime): self
    {
        $this->maxTime = $maxTime;

        return $this;
    }

    /** {@inheritDoc} */
    public function withDlq(?string $dlqTopic = null): self
    {
        if (! isset($this->topics[0])) {
            throw ConsumerException::dlqCanNotBeSetWithoutSubscribingToAnyTopics();
        }

        if ($dlqTopic === null) {
            $dlqTopic = $this->topics[0].'-dlq';
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

    /** {@inheritDoc} */
    public function withMiddleware(Middleware|callable|string $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /** {@inheritDoc} */
    public function withSecurityProtocol(string $securityProtocol): self
    {
        $this->securityProtocol = $securityProtocol;

        return $this;
    }

    /** {@inheritDoc} */
    public function withAutoCommit(bool $autoCommit = true): self
    {
        $this->autoCommit = $autoCommit;

        return $this;
    }

    public function withManualCommit(): self
    {
        $this->autoCommit = false;

        return $this;
    }

    /** {@inheritDoc} */
    public function withRebalanceStrategy(RebalanceStrategy|string $strategy): self
    {
        if (is_string($strategy)) {
            $enum = RebalanceStrategy::tryFrom($strategy);

            if ($enum === null) {
                throw new InvalidArgumentException(
                    "Invalid rebalance strategy [{$strategy}]. Valid strategies are: ".implode(', ', RebalanceStrategy::values())
                );
            }

            $strategy = $enum;
        }

        return $this->withOption('partition.assignment.strategy', $strategy->value);
    }

    /** {@inheritDoc} */
    public function withOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->withOption($name, $value);
        }

        return $this;
    }

    /** {@inheritDoc} */
    public function withOption(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function stopAfterLastMessage(bool $stopAfterLastMessage = true): self
    {
        $this->stopAfterLastMessage = $stopAfterLastMessage;

        return $this;
    }

    /** {@inheritDoc} */
    public function skipFailedMessages(bool $skipFailedMessages = true): self
    {
        $this->skipFailedMessages = $skipFailedMessages;

        return $this;
    }

    /** {@inheritDoc} */
    public function retryFailedMessages(int $times, int $backoffInMs = 0): self
    {
        if ($times < 0 || $backoffInMs < 0) {
            throw new InvalidArgumentException('The number of retries and the backoff must not be negative.');
        }

        $this->failedMessageRetries = $times;
        $this->failedMessageRetryBackoff = $backoffInMs;

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

    public function withPartitionAssignmentCallback(callable $callback): self
    {
        $this->partitionAssignmentCallback = $callback(...);

        $this->withRebalanceCb(function ($consumer, $err, $partitions = null) use ($callback) {
            if ($err === RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
                $consumer->assign($partitions);
                $callback($partitions);
            } elseif ($err === RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
                $consumer->assign(null);
            }
        });

        return $this;
    }

    public function assignPartitionsWithOffsets(callable $offsetProvider): self
    {
        // Set up the rebalance callback to handle dynamic partition assignment with offsets
        $this->withRebalanceCb(function ($consumer, $err, $partitions = null) use ($offsetProvider) {
            if ($err === RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
                // Get offset assignments from the provided callback
                $partitionsWithOffsets = $offsetProvider($partitions);

                // Assign the partitions with their offsets
                $consumer->assign($partitionsWithOffsets);
            } elseif ($err === RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
                $consumer->assign(null);
            }
        });

        return $this;
    }

    /** {@inheritDoc} */
    public function build(): MessageConsumer
    {
        $config = new Config(
            broker: $this->brokers,
            topics: $this->topics,
            securityProtocol: $this->getSecurityProtocol(),
            groupId: $this->groupId,
            consumer: new CallableConsumer($this->handler, $this->middlewares),
            sasl: $this->saslConfig,
            dlq: $this->dlq,
            maxMessages: $this->maxMessages,
            autoCommit: $this->autoCommit,
            customOptions: $this->options,
            stopAfterLastMessage: $this->stopAfterLastMessage,
            callbacks: $this->callbacks,
            beforeConsumingCallbacks: $this->beforeConsumingCallbacks,
            afterConsumingCallbacks: $this->afterConsumingCallbacks,
            maxTime: $this->maxTime,
            partitionAssignment: $this->partitionAssignment,
            whenStopConsuming: $this->onStopConsuming,
            consumerTimeoutInMs: $this->consumerTimeoutInMs,
            skipFailedMessages: $this->skipFailedMessages,
            failedMessageRetries: $this->failedMessageRetries,
            failedMessageRetryBackoff: $this->failedMessageRetryBackoff,
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
}
