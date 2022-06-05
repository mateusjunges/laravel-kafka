<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Support\Timer;
use Junges\Kafka\Config\BatchConfig;
use Junges\Kafka\Config\NullBatchConfig;
use Junges\Kafka\Consumers\CallableConsumer;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Commit\Contracts\CommitterFactory;
use Junges\Kafka\Exceptions\KafkaConsumerException;
use Junges\Kafka\Support\Testing\Fakes\ConsumerFake;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;

class ConsumerBuilderFake
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
    private bool $batchingEnabled = false;
    private int $batchSizeLimit = 0;
    private int $batchReleaseInterval = 0;
    /** @var \Junges\Kafka\Contracts\KafkaConsumerMessage[] */
    private array $messages = [];

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
        return new ConsumerBuilderFake(
            brokers: $brokers,
            topics: $topics,
            groupId: $groupId
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

            if (!collect($this->topics)->contains($topic)) {
                $this->topics[] = $topic;
            }
        }

        return $this;
    }

    /**
     * Set the brokers the kafka consumer should use.
     *
     * @param ?string $brokers
     * @return $this
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
     * @return $this
     */
    public function withConsumerGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Specify the commit batch size.
     *
     * @param int $size
     * @return $this
     */
    public function withCommitBatchSize(int $size): self
    {
        $this->commit = $size;

        return $this;
    }

    /**
     * Specify the class used to handle consumed messages.
     *
     * @param callable $handler
     * @return $this
     */
    public function withHandler(callable $handler): self
    {
        $this->handler = Closure::fromCallable($handler);

        return $this;
    }

    /**
     * Specify the class that should be used to deserialize messages.
     *
     * @param MessageDeserializer $deserializer
     * @return $this
     */
    public function usingDeserializer(MessageDeserializer $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /**
     * Specify the factory that should be used to build the committer.
     *
     * @param CommitterFactory $committerFactory
     * @return $this
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
     * @return $this
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
     * @throws \Junges\Kafka\Exceptions\KafkaConsumerException
     */
    public function withDlq(?string $dlqTopic = null): self
    {
        if (!isset($this->topics[0])) {
            throw KafkaConsumerException::dlqCanNotBeSetWithoutSubscribingToAnyTopics();
        }

        if (null === $dlqTopic) {
            $dlqTopic = $this->topics[0] . '-dlq';
        }

        $this->dlq = $dlqTopic;

        return $this;
    }

    /**
     * Set the Sasl configuration.
     *
     * @param Sasl $saslConfig
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function withSecurityProtocol(string $securityProtocol): self
    {
        $this->securityProtocol = $securityProtocol;

        return $this;
    }

    /**
     * Enable or disable consumer auto commit option.
     *
     * @return $this
     */
    public function withAutoCommit(bool $autoCommit = true): self
    {
        $this->autoCommit = $autoCommit;

        return $this;
    }

    /**
     * Set the configuration options.
     *
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
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function withOption(string $name, string $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Enables messages batching
     *
     * @return $this
     */
    public function enableBatching(): self
    {
        $this->batchingEnabled = true;

        return $this;
    }

    /**
     * Set batch size limit
     * Batch of messages will be released after batch size exceeds given limit
     *
     * @param int $batchSizeLimit
     * @return $this
     */
    public function withBatchSizeLimit(int $batchSizeLimit): self
    {
        $this->batchSizeLimit = $batchSizeLimit;

        return $this;
    }

    /**
     * Set batch release interval in milliseconds
     * Batch of messages will be released after timer exceeds given interval
     *
     * @param int $batchReleaseIntervalInMilliseconds
     * @return $this
     */
    public function withBatchReleaseInterval(int $batchReleaseIntervalInMilliseconds): self
    {
        $this->batchReleaseInterval = $batchReleaseIntervalInMilliseconds;

        return $this;
    }

    /**
     * Build the Kafka consumer.
     *
     * @return Consumer
     */
    public function build(): ConsumerFake
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

        return new ConsumerFake(
            $config,
            $this->messages
        );
    }

    /**
     * Validates each topic before subscribing.
     *
     * @param mixed $topic
     * @return void
     */
    private function validateTopic(mixed $topic)
    {
        if (!is_string($topic)) {
            $type = ucfirst(gettype($topic));

            throw new InvalidArgumentException("The topic name should be a string value. [{$type}] given.");
        }
    }

    /**
     * Get security protocol depending if sasl is been set.
     *
     * @return string
     */
    private function getSecurityProtocol(): string
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
    private function getBatchConfig(): HandlesBatchConfiguration
    {
        if (!$this->batchingEnabled) {
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
