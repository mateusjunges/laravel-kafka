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
use Junges\Kafka\Config\SaslMechanism;
use Junges\Kafka\Config\SecurityProtocol;
use Junges\Kafka\Contracts\CommitterFactory;
use Junges\Kafka\Contracts\Consumer as ConsumerContract;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\Middleware;
use Junges\Kafka\Exceptions\ConsumerException;
use LogicException;
use RdKafka\TopicPartition;

class Builder
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

    protected bool $stopWhenEmpty = false;

    protected bool $skipFailedMessages = false;

    protected int $failedMessageRetries = 0;

    /** @var int|list<int> */
    protected int|array $failedMessageRetryBackoff = 0;

    /** @var list<callable> */
    protected array $beforeConsumingCallbacks = [];

    /** @var list<callable> */
    protected array $afterConsumingCallbacks = [];

    /** @var array<int, TopicPartition> */
    protected array $partitionAssignment = [];

    protected ?Closure $onStopConsuming = null;

    protected ?Closure $onPartitionsAssigned = null;

    protected ?Closure $onPartitionsRevoked = null;

    protected ?Closure $offsetResolver = null;

    protected bool $useDefaultDlq = false;

    protected ?Closure $onMessageFailed = null;

    protected string $brokers;

    protected ?string $groupId;

    protected int $consumerTimeoutInMs;

    protected ?string $name = null;

    /** The producer settings of the connection, used to publish failed messages to the dead letter queue. */
    protected ConnectionConfig $connection;

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
        $this->connection = $connection;

        $this->handler = function () {};
        $this->maxMessages = -1;
        $this->middlewares = [];

        $this->deserializer = app($connection->deserializer ?? MessageDeserializer::class);
    }

    /** Creates a new ConsumerBuilder instance for the given connection. */
    public static function create(ConnectionConfig $connection, array $topics = [], ?string $groupId = null): static
    {
        return new static(
            connection: $connection,
            topics: $topics,
            groupId: $groupId
        );
    }

    /**
     * Set the name of the consumer, which identifies it in events and when restarting it. Consumer classes
     * are named after their class, and other consumers after the topics they consume, by default.
     */
    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /** Subscribe to a Kafka topic. */
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

    /** Set the brokers the kafka consumer should use, instead of the connection brokers. */
    public function withBrokers(string $brokers): self
    {
        $this->brokers = $brokers;

        return $this;
    }

    /** Set the consumer group, instead of the group of the connection. */
    public function withGroupId(string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /** Specify the handler of the consumed messages, a callable receiving the message and the consumer. */
    public function withHandler(callable|Handler $handler): self
    {
        $this->handler = $handler instanceof Handler
            ? $handler
            : $handler(...);

        return $this;
    }

    /** Specify the class that should be used to deserialize messages. */
    public function usingDeserializer(MessageDeserializer $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /** Specify the factory that should be used to build the committer. */
    public function usingCommitterFactory(CommitterFactory $committerFactory): self
    {
        $this->committerFactory = $committerFactory;

        return $this;
    }

    /** Stop consuming after handling the given number of messages. */
    public function stopAfterMessages(int $messages): self
    {
        $this->maxMessages = $messages;

        return $this;
    }

    /** Stop consuming after the given number of seconds. */
    public function stopAfterSeconds(int $seconds): self
    {
        $this->maxTime = $seconds;

        return $this;
    }

    /**
     * Set the Dead Letter Queue to be used. When no topic is given, it is named after the first
     * consumed topic, followed by "-dlq", when the consumer is built.
     */
    public function withDlq(?string $dlqTopic = null): self
    {
        $this->dlq = $dlqTopic;
        $this->useDefaultDlq = $dlqTopic === null;

        return $this;
    }

    /**
     * Authenticate this consumer with SASL, using the given credentials instead of the ones of the connection.
     * The security protocol must be SASL_PLAINTEXT or SASL_SSL. When it is not given, SASL_SSL is used if
     * the connection is encrypted, and SASL_PLAINTEXT otherwise.
     */
    public function withSasl(
        string $username,
        string $password,
        SaslMechanism|string $mechanism,
        SecurityProtocol|string|null $securityProtocol = null,
    ): self {
        $this->saslConfig = new Sasl(
            username: $username,
            password: $password,
            mechanism: $mechanism instanceof SaslMechanism ? $mechanism->value : $mechanism,
        );

        // Without a given protocol, the encryption of the connection is kept.
        $this->securityProtocol = $securityProtocol === null
            ? SecurityProtocol::forSasl($this->securityProtocol)->value
            : ($securityProtocol instanceof SecurityProtocol ? $securityProtocol->value : $securityProtocol);

        return $this;
    }

    /**
     * Add a middleware the messages go through before being handled. Middlewares run in the order they are added,
     * and receive the message and the next step of the pipeline. Middleware classes given by name are resolved
     * from the service container.
     *
     * @param  Middleware|callable(ConsumerMessage, callable): mixed|class-string<Middleware>  $middleware
     */
    public function withMiddleware(Middleware|callable|string $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /** Enable or disable consumer auto commit option. */
    public function withAutoCommit(bool $autoCommit = true): self
    {
        $this->autoCommit = $autoCommit;

        return $this;
    }

    /** Enables manual commit. */
    public function withManualCommit(): self
    {
        $this->autoCommit = false;

        return $this;
    }

    /** Set the partition assignment (rebalance) strategy for consumer groups. */
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

    /** Set the configuration options. */
    public function withOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->withOption($name, $value);
        }

        return $this;
    }

    /** Set a specific configuration option. */
    public function withOption(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /** Stop consuming once there are no messages left in the assigned partitions. */
    public function stopWhenEmpty(bool $stopWhenEmpty = true): self
    {
        $this->stopWhenEmpty = $stopWhenEmpty;

        return $this;
    }

    /**
     * Skip messages that fail when there is no dead letter queue, committing their offsets.
     * By default, the consumer stops without committing the offset of the failed message.
     */
    public function skipFailedMessages(bool $skipFailedMessages = true): self
    {
        $this->skipFailedMessages = $skipFailedMessages;

        return $this;
    }

    /**
     * Call the handler of a failed message again, up to the given number of times, before handling it as failed.
     * The backoff is the time to wait before each retry, in milliseconds. An array sets the time to wait before
     * each retry in order, like [1000, 5000, 10000], and its last value is used for the remaining retries.
     *
     * @param  int|list<int>  $backoffInMs
     */
    public function retryFailedMessages(int $times, int|array $backoffInMs = 0): self
    {
        $backoffs = is_array($backoffInMs) ? $backoffInMs : [$backoffInMs];

        if ($times < 0 || collect($backoffs)->contains(fn (mixed $backoff) => ! is_int($backoff) || $backoff < 0)) {
            throw new InvalidArgumentException('The number of retries must not be negative, and the backoff must be made of non negative integers.');
        }

        $this->failedMessageRetries = $times;
        $this->failedMessageRetryBackoff = $backoffInMs;

        return $this;
    }

    /** Defines a callback that runs before consuming the message. */
    public function beforeConsuming(callable $callable): self
    {
        $this->beforeConsumingCallbacks[] = $callable(...);

        return $this;
    }

    /** Defines a callback that runs after consuming the message. */
    public function afterConsuming(callable $callable): self
    {
        $this->afterConsumingCallbacks[] = $callable(...);

        return $this;
    }

    /** Assigns a set of partitions this consumer should consume from. */
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

    /** Defines a callback to be executed when consumer stops consuming messages. */
    public function onStopConsuming(callable $onStopConsuming): self
    {
        $this->onStopConsuming = $onStopConsuming(...);

        return $this;
    }

    /**
     * Defines a callback to be executed when a message is handled as failed, once its retries are used, before
     * it is sent to the dead letter queue, skipped, or stops the consumer. It receives the message and the exception.
     */
    public function onMessageFailed(callable $callback): self
    {
        $this->onMessageFailed = $callback(...);

        return $this;
    }

    /** Set a callback that receives the partitions assigned to this consumer and the consumer, on every rebalance. */
    public function onPartitionsAssigned(callable $callback): self
    {
        $this->onPartitionsAssigned = $callback(...);

        return $this;
    }

    /**
     * Set a callback that receives the partitions revoked from this consumer and the consumer, on every rebalance.
     * It is called before the partitions are removed from the assignment, so offsets can still be committed.
     */
    public function onPartitionsRevoked(callable $callback): self
    {
        $this->onPartitionsRevoked = $callback(...);

        return $this;
    }

    /**
     * Set a callback that receives the partitions assigned to this consumer, on every rebalance,
     * and returns them with the offsets the consumer should start reading from.
     */
    public function resolveOffsetsUsing(callable $resolver): self
    {
        $this->offsetResolver = $resolver(...);

        return $this;
    }

    /** Build the Kafka consumer. */
    public function build(): ConsumerContract
    {
        return new Consumer($this->makeConfig(), $this->deserializer, $this->committerFactory);
    }

    /** Create the configuration of the consumer. */
    protected function makeConfig(): Config
    {
        return new Config(
            broker: $this->brokers,
            topics: $this->topics,
            securityProtocol: $this->securityProtocol,
            groupId: $this->groupId,
            handler: new MessageHandler($this->handler, $this->middlewares, $this->onMessageFailed),
            sasl: $this->saslConfig,
            dlq: $this->resolveDlq(),
            maxMessages: $this->maxMessages,
            autoCommit: $this->autoCommit,
            customOptions: $this->options,
            // Failed messages are published to the dead letter queue outside of any transaction.
            producerOptions: array_diff_key($this->connection->producerOptions, ['transactional.id' => true]),
            flushRetries: $this->connection->flushRetries,
            flushTimeoutInMs: $this->connection->flushTimeoutInMs,
            flushRetrySleepInMs: $this->connection->flushRetrySleepInMs,
            stopAfterLastMessage: $this->stopWhenEmpty,
            callbacks: $this->resolveCallbacks(),
            beforeConsumingCallbacks: $this->beforeConsumingCallbacks,
            afterConsumingCallbacks: $this->afterConsumingCallbacks,
            maxTime: $this->maxTime,
            partitionAssignment: $this->partitionAssignment,
            whenStopConsuming: $this->onStopConsuming,
            consumerTimeoutInMs: $this->consumerTimeoutInMs,
            skipFailedMessages: $this->skipFailedMessages,
            failedMessageRetries: $this->failedMessageRetries,
            failedMessageRetryBackoff: $this->failedMessageRetryBackoff,
            name: $this->name,
            connection: $this->connection->name,
            onPartitionsAssigned: $this->onPartitionsAssigned,
            onPartitionsRevoked: $this->onPartitionsRevoked,
            offsetResolver: $this->offsetResolver,
        );
    }

    /**
     * Resolve the dead letter queue topic. When no name is given, it is named after the first
     * subscribed topic, or the topic of the first assigned partition.
     *
     * @throws ConsumerException
     */
    protected function resolveDlq(): ?string
    {
        if (! $this->useDefaultDlq) {
            return $this->dlq;
        }

        $topic = $this->topics[0] ?? ($this->partitionAssignment[0] ?? null)?->getTopic();

        if ($topic === null) {
            throw ConsumerException::dlqCanNotBeSetWithoutSubscribingToAnyTopics();
        }

        return $topic.'-dlq';
    }

    /**
     * Resolve the configuration callbacks. The consumer assigns partitions itself on every rebalance, calling the
     * partition callbacks, unless a rebalance callback replaces the default assignment, so they can't be combined.
     */
    protected function resolveCallbacks(): array
    {
        $usesPartitionCallbacks = $this->onPartitionsAssigned instanceof Closure
            || $this->onPartitionsRevoked instanceof Closure
            || $this->offsetResolver instanceof Closure;

        if ($usesPartitionCallbacks && isset($this->callbacks['setRebalanceCb'])) {
            throw new LogicException('A rebalance callback can not be combined with onPartitionsAssigned(), onPartitionsRevoked() or resolveOffsetsUsing(), which rely on the default partition assignment.');
        }

        return $this->callbacks;
    }

    /** Validates each topic before subscribing. */
    protected function validateTopic(mixed $topic): void
    {
        if (! is_string($topic)) {
            $type = ucfirst(gettype($topic));

            throw new InvalidArgumentException("The topic name should be a string value. [{$type}] given.");
        }
    }
}
