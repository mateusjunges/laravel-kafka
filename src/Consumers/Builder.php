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
use Junges\Kafka\Contracts\Consumer as ConsumerContract;
use Junges\Kafka\Contracts\ConsumerBuilder as ConsumerBuilderContract;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\Middleware;
use Junges\Kafka\Exceptions\ConsumerException;
use LogicException;
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

    protected ?Closure $offsetResolver = null;

    protected bool $useDefaultDlq = false;

    protected ?Closure $onMessageFailed = null;

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

        $this->deserializer = app($connection->deserializer ?? MessageDeserializer::class);
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
    public function stopAfterMessages(int $messages): self
    {
        $this->maxMessages = $messages;

        return $this;
    }

    /** {@inheritDoc} */
    public function stopAfterSeconds(int $seconds): self
    {
        $this->maxTime = $seconds;

        return $this;
    }

    /** {@inheritDoc} */
    public function withDlq(?string $dlqTopic = null): self
    {
        $this->dlq = $dlqTopic;
        $this->useDefaultDlq = $dlqTopic === null;

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
    public function stopWhenEmpty(bool $stopWhenEmpty = true): self
    {
        $this->stopWhenEmpty = $stopWhenEmpty;

        return $this;
    }

    /** {@inheritDoc} */
    public function skipFailedMessages(bool $skipFailedMessages = true): self
    {
        $this->skipFailedMessages = $skipFailedMessages;

        return $this;
    }

    /** {@inheritDoc} */
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

    /** {@inheritDoc} */
    public function onMessageFailed(callable $callback): self
    {
        $this->onMessageFailed = $callback(...);

        return $this;
    }

    /** {@inheritDoc} */
    public function onPartitionsAssigned(callable $callback): self
    {
        $this->onPartitionsAssigned = $callback(...);

        return $this;
    }

    /** {@inheritDoc} */
    public function resolveOffsetsUsing(callable $resolver): self
    {
        $this->offsetResolver = $resolver(...);

        return $this;
    }

    /** {@inheritDoc} */
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
     * Resolve the configuration callbacks, combining the partitions assigned callback and
     * the offset resolver into a single rebalance callback.
     */
    protected function resolveCallbacks(): array
    {
        if (! $this->onPartitionsAssigned instanceof Closure && ! $this->offsetResolver instanceof Closure) {
            return $this->callbacks;
        }

        if (isset($this->callbacks['setRebalanceCb'])) {
            throw new LogicException('A rebalance callback can not be combined with onPartitionsAssigned() or resolveOffsetsUsing(), which set their own.');
        }

        $onAssign = $this->onPartitionsAssigned;
        $offsetResolver = $this->offsetResolver;

        return [...$this->callbacks, 'setRebalanceCb' => function ($consumer, $err, $partitions = null) use ($onAssign, $offsetResolver): void {
            if ($err === RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
                if ($offsetResolver instanceof Closure) {
                    $partitions = $offsetResolver($partitions);
                }

                $consumer->assign($partitions);

                if ($onAssign instanceof Closure) {
                    $onAssign($partitions);
                }
            } elseif ($err === RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
                $consumer->assign(null);
            }
        }];
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
