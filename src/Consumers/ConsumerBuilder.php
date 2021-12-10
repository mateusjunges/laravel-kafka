<?php

namespace Junges\Kafka\Consumers;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Commit\Contracts\CommitterFactory;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\MessageDeserializer;

class ConsumerBuilder
{
    private array $topics;
    private int $commit;
    private ?string $groupId;
    private Closure $handler;
    private int $maxMessages;
    private int $maxCommitRetries;
    private string $brokers;
    private array $middlewares;
    private ?Sasl $saslConfig = null;
    private ?string $dlq = null;
    private string $securityProtocol;
    private bool $autoCommit;
    private array $options;
    private MessageDeserializer $deserializer;
    private ?CommitterFactory $committerFactory = null;

    /**
     * @param string $brokers
     * @param array $topics
     * @param string|null $groupId
     */
    private function __construct(string $brokers, array $topics = [], string $groupId = null)
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
     * @param string $brokers
     * @param array $topics
     * @param string|null $groupId
     * @return static
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
     * Subscribe to a Kafka topic.
     *
     * @param mixed ...$topics
     * @return $this
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
     * Set the brokers the kafka consumer should use.
     *
     * @param string $brokers
     * @return $this
     */
    public function withBrokers(string $brokers): self
    {
        $this->brokers = $brokers;

        return $this;
    }

    /**
     * @param string $groupId
     * @return $this
     */
    public function withConsumerGroupId(string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function withCommitBatchSize(int $size): self
    {
        $this->commit = $size;

        return $this;
    }

    /**
     * @param callable $handler
     * @return $this
     */
    public function withHandler(callable $handler): self
    {
        $this->handler = Closure::fromCallable($handler);

        return $this;
    }

    public function usingDeserializer(MessageDeserializer $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    public function usingCommitterFactory(CommitterFactory $committerFactory): self
    {
        $this->committerFactory = $committerFactory;

        return $this;
    }

    /**
     * @param int $maxMessages
     * @return $this
     */
    public function withMaxMessages(int $maxMessages): self
    {
        $this->maxMessages = $maxMessages;

        return $this;
    }

    /**
     * @param int $maxCommitRetries
     * @return $this
     */
    public function withMaxCommitRetries(int $maxCommitRetries): self
    {
        $this->maxCommitRetries = $maxCommitRetries;

        return $this;
    }

    /**
     * Set the Dead Letter Queue to be used. If null, the dlq is created from the topic name.
     *
     * @param string|null $dlqTopic
     * @return $this
     */
    public function withDlq(?string $dlqTopic = null): self
    {
        if (null === $dlqTopic) {
            $dlqTopic = $this->topics[0] . '-dlq';
        }

        $this->dlq = $dlqTopic;

        return $this;
    }

    /**
     * @param Sasl $saslConfig
     * @return $this
     */
    public function withSasl(Sasl $saslConfig): self
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    /**
     * The middlewares get executed in the order they are defined.
     *
     * The middleware is a callable in which the first argument is the message itself and the second is the next handler
     *
     * @param callable(mixed, callable): void $middleware
     * @return $this
     */
    public function withMiddleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param string $securityProtocol
     * @return $this
     */
    public function withSecurityProtocol(string $securityProtocol): self
    {
        $this->securityProtocol = $securityProtocol;

        return $this;
    }

    /**
     * @return $this
     */
    public function withAutoCommit(): self
    {
        $this->autoCommit = true;

        return $this;
    }

    /**
     * Set the configuration options.
     * @param array $options
     * @return $this
     */
    public function withOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->withOption($name, $value);
        }

        return $this;
    }

    /**
     * Set a specific configuration option.
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function withOption(string $name, string $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function build(): Consumer
    {
        $config = new Config(
            broker: $this->brokers,
            topics: $this->topics,
            securityProtocol: $this->securityProtocol,
            commit: $this->commit,
            groupId: $this->groupId,
            consumer: new CallableConsumer($this->handler, $this->middlewares),
            sasl: $this->saslConfig,
            dlq: $this->dlq,
            maxMessages: $this->maxMessages,
            maxCommitRetries: $this->maxCommitRetries,
            autoCommit: $this->autoCommit,
            customOptions: $this->options
        );

        return new Consumer($config, $this->deserializer, $this->committerFactory);
    }

    private function validateTopic(mixed $topic)
    {
        if (! is_string($topic)) {
            $type = ucfirst(gettype($topic));

            throw new InvalidArgumentException("The topic name should be a string value. [{$type}] given.");
        }
    }
}
