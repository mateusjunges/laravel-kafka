<?php

namespace Junges\Kafka\Consumers;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;

class ConsumerBuilder
{
    private array $topics;
    private int $commit;
    private string $groupId;
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

    /**
     * @param string $brokers
     * @param string $groupId
     * @param array $topics
     */
    private function __construct(string $brokers, string $groupId, array $topics)
    {
        foreach ($topics as $topic) {
            if (!is_string($topic)) {
                $type = ucfirst(gettype($topic));
                throw new InvalidArgumentException("The topic name should be a string value. {$type} given.");
            }
        }

        $this->brokers = $brokers;
        $this->groupId = $groupId;
        $this->topics = $topics;

        $this->commit = 1;
        $this->handler = function () {
        };
        $this->maxMessages = -1;
        $this->maxCommitRetries = 6;
        $this->middlewares = [];
        $this->securityProtocol = 'PLAINTEXT';
        $this->autoCommit = false;
        $this->options = [];
    }

    /**
     * @param string $brokers
     * @param string $groupId
     * @param array $topics
     * @return static
     */
    public static function create(string $brokers, string $groupId, array $topics): self
    {
        return new ConsumerBuilder($brokers, $groupId, $topics);
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

        return new Consumer($config);
    }
}