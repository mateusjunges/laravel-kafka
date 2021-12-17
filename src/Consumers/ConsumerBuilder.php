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
    private ?int $commit;
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
     * Creates a new ConsumerBuilder instance.
     *
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
     * Creates a new ConsumerBuilder instance based on a pre-configured consumer.
     *
     * @param array $config
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public static function createFromConsumerConfig(array $config): ConsumerBuilder
    {
        $consumer = (new static(
                brokers: $config['brokers'],
                topics: $config['topics'],
                groupId: $config['group_id']
            ))
            ->withAutoCommit($config['auto_commit'])
            ->withMaxCommitRetries($config['max_commit_retries'])
            ->withCommitBatchSize($config['commit_batch_size'])
            ->withMaxMessages($config['max_messages'])
            ->withSecurityProtocol($config['security_protocol'])
            ->withOption('auto.offset.reset', $config['offset_reset'])
            ->withOptions($config['options']);

        if ($config['dlq_topic']) {
            $consumer->withDlq($config['dlq_topic']);
        }

        return $consumer;
    }

    /**
     * Subscribe to a Kafka topic.
     *
     * @param mixed ...$topics
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
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
     * Unsubscribe from a kafka topic.
     *
     * @param ...$topics
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function unsubscribe(...$topics): self
    {
        if (is_array($topics[0])) {
            $topics = $topics[0];
        }

        foreach ($topics as $topic) {
            if (! is_string($topic)) {
                continue;
            }

            unset($this->topics[array_search($topic, $this->topics)]);
        }

        return $this;
    }

    /**
     * Set the brokers the kafka consumer should use.
     *
     * @param ?string $brokers
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withBrokers(?string $brokers): self
    {
        $this->brokers = $brokers ?? config('kafka.brokers');

        return $this;
    }

    /**
     * Specify the consumer group id.
     *
     * @param ?string $groupId
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withConsumerGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Specify the commit batch size.
     *
     * @param ?int $size
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withCommitBatchSize(?int $size): self
    {
        $this->commit = $size;

        return $this;
    }

    /**
     * Specify the class used to handle consumed messages.
     *
     * @param callable $handler
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withHandler(callable $handler): self
    {
        $this->handler = Closure::fromCallable($handler);

        return $this;
    }

    /**
     * Specify the class that should be used to deserialize messages.
     *
     * @param \Junges\Kafka\Contracts\MessageDeserializer $deserializer
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function usingDeserializer(MessageDeserializer $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /**
     * Specify the factory that should be used to build the committer.
     *
     * @param \Junges\Kafka\Commit\Contracts\CommitterFactory $committerFactory
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function usingCommitterFactory(CommitterFactory $committerFactory): self
    {
        $this->committerFactory = $committerFactory;

        return $this;
    }

    /**
     * Define the max number of messages that should be consumed.
     *
     * @param int $maxMessages
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withMaxMessages(int $maxMessages): self
    {
        $this->maxMessages = $maxMessages;

        return $this;
    }

    /**
     * Specify the max retries attempts.
     *
     * @param int $maxCommitRetries
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
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
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
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
     * Set the Sasl configuration.
     *
     * @param \Junges\Kafka\Config\Sasl $saslConfig
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withSasl(Sasl $saslConfig): self
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    /**
     * Specify middlewares to be executed before handling the message.
     * The middlewares get executed in the order they are defined.
     * The middleware is a callable in which the first argument is the message itself and the second is the next handler
     *
     * @param callable(mixed, callable): void $middleware
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withMiddleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Specify the security protocol that should be used.
     *
     * @param string $securityProtocol
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withSecurityProtocol(string $securityProtocol): self
    {
        $this->securityProtocol = $securityProtocol;

        return $this;
    }

    /**
     * Enable or disable consumer auto commit option.
     *
     * @param bool $autoCommit
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withAutoCommit(bool $autoCommit = true): ConsumerBuilder
    {
        $this->autoCommit = $autoCommit;

        return $this;
    }

    /**
     * Set the configuration options.
     *
     * @param array $options
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
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
     *
     * @param string $name
     * @param string $value
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withOption(string $name, string $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Build the Kafka consumer.
     *
     * @return \Junges\Kafka\Consumers\Consumer
     */
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

    /**
     * Validates each topic before subscribing.
     *
     * @param mixed $topic
     * @return void
     */
    private function validateTopic(mixed $topic)
    {
        if (! is_string($topic)) {
            $type = ucfirst(gettype($topic));

            throw new InvalidArgumentException("The topic name should be a string value. [{$type}] given.");
        }
    }
}
