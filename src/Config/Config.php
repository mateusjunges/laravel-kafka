<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

use Closure;
use Junges\Kafka\Consumers\MessageHandler;
use RdKafka\Conf;
use RdKafka\TopicPartition;

class Config
{
    final public const array PRODUCER_ONLY_CONFIG_OPTIONS = [
        'transactional.id',
        'transaction.timeout.ms',
        'enable.idempotence',
        'enable.gapless.guarantee',
        'queue.buffering.max.messages',
        'queue.buffering.max.kbytes',
        'queue.buffering.max.ms',
        'linger.ms',
        'message.send.max.retries',
        'retries',
        'retry.backoff.ms',
        'queue.buffering.backpressure.threshold',
        'compression.codec',
        'compression.type',
        'batch.num.messages',
        'batch.size',
        'delivery.report.only.error',
        'dr_cb',
        'dr_msg_cb',
        'sticky.partitioning.linger.ms',
    ];

    final public const array CONSUMER_ONLY_CONFIG_OPTIONS = [
        'partition.assignment.strategy',
        'session.timeout.ms',
        'heartbeat.interval.ms',
        'group.protocol.type',
        'coordinator.query.interval.ms',
        'max.poll.interval.ms',
        'enable.auto.commit',
        'auto.commit.interval.ms',
        'enable.auto.offset.store',
        'queued.min.messages',
        'queued.max.messages.kbytes',
        'fetch.wait.max.ms',
        'fetch.message.max.bytes',
        'max.partition.fetch.bytes',
        'fetch.max.bytes',
        'fetch.min.bytes',
        'fetch.error.backoff.ms',
        'offset.store.method',
        'isolation.level',
        'consume_cb',
        'rebalance_cb',
        'offset_commit_cb',
        'enable.partition.eof',
        'check.crcs',
        'allow.auto.create.topics',
        'auto.offset.reset',
    ];

    public function __construct(
        private readonly string $broker,
        private readonly array $topics,
        private readonly ?string $securityProtocol = null,
        private readonly ?string $groupId = null,
        private readonly ?MessageHandler $handler = null,
        private readonly ?Sasl $sasl = null,
        private readonly ?string $dlq = null,
        private readonly int $maxMessages = -1,
        private readonly bool $autoCommit = true,
        private readonly array $customOptions = [],
        private readonly bool $stopAfterLastMessage = false,
        private readonly int $restartInterval = 1000,
        private readonly array $callbacks = [],
        private readonly array $beforeConsumingCallbacks = [],
        private readonly array $afterConsumingCallbacks = [],
        private readonly int $maxTime = 0,
        private readonly array $partitionAssignment = [],
        private readonly ?Closure $whenStopConsuming = null,
        public readonly int $flushRetries = 10,
        public readonly int $flushTimeoutInMs = 1000,
        public readonly int $flushRetrySleepInMs = 100,
        public readonly int $consumerTimeoutInMs = 2000,
        private readonly bool $skipFailedMessages = false,
        private readonly int $failedMessageRetries = 0,
        private readonly int|array $failedMessageRetryBackoff = 0,
    ) {}

    public function getTopics(): array
    {
        return $this->topics;
    }

    public function getHandler(): MessageHandler
    {
        return $this->handler;
    }

    public function getDlq(): ?string
    {
        return $this->dlq;
    }

    public function getMaxMessages(): int
    {
        return $this->maxMessages;
    }

    public function getMaxTime(): int
    {
        return $this->maxTime;
    }

    public function shouldStopAfterLastMessage(): bool
    {
        return $this->stopAfterLastMessage;
    }

    /** Determine if failed messages are skipped when there is no dead letter queue, instead of stopping the consumer. */
    public function shouldSkipFailedMessages(): bool
    {
        return $this->skipFailedMessages;
    }

    public function getFailedMessageRetries(): int
    {
        return $this->failedMessageRetries;
    }

    /**
     * Get the time to wait before retrying a failed message, as expected by the retry() helper. With an
     * array, each retry waits for the value at its position, or the last value when there are more retries.
     */
    public function getFailedMessageRetrySleep(): int|Closure
    {
        $backoff = $this->failedMessageRetryBackoff;

        if (! is_array($backoff)) {
            return $backoff;
        }

        return fn (int $attempt): int => $backoff[$attempt - 1] ?? $backoff[array_key_last($backoff)] ?? 0;
    }

    /**
     * Determine if offsets must be stored by the consumer after each message is processed,
     * instead of being stored by librdkafka as soon as each message is fetched.
     */
    public function shouldStoreOffsetsAfterProcessing(): bool
    {
        return $this->autoCommit;
    }

    public function getConsumerOptions(): array
    {
        $options = [
            'metadata.broker.list' => $this->broker,
            'bootstrap.servers' => $this->broker,
            'group.id' => $this->groupId,
            'enable.auto.commit' => $this->autoCommit ? 'true' : 'false',
            ...$this->getSecurityProtocolOptions(),
        ];

        // By default, librdkafka stores the offset of each message as soon as it is fetched and commits
        // it in the background, even when the handler fails. With auto commit enabled, offsets are
        // stored by the consumer instead, only after each message is processed or skipped.
        $overrides = $this->shouldStoreOffsetsAfterProcessing()
            ? ['enable.auto.offset.store' => 'false']
            : [];

        return collect(array_merge($options, $this->customOptions, $this->getSaslOptions(), $overrides))
            ->reject(fn (mixed $option, string $key) => in_array($key, self::PRODUCER_ONLY_CONFIG_OPTIONS))
            ->map($this->normalizeOption(...))
            ->toArray();
    }

    public function getProducerOptions(): array
    {
        $config = [
            'bootstrap.servers' => $this->broker,
            'metadata.broker.list' => $this->broker,
            ...$this->getSecurityProtocolOptions(),
        ];

        return collect(array_merge($config, $this->customOptions, $this->getSaslOptions()))
            ->reject(fn (mixed $option, string $key) => in_array($key, self::CONSUMER_ONLY_CONFIG_OPTIONS))
            ->map($this->normalizeOption(...))
            ->toArray();
    }

    public function getRestartInterval(): int
    {
        return $this->restartInterval;
    }

    public function getConfigCallbacks(): array
    {
        return $this->callbacks;
    }

    public function getBeforeConsumingCallbacks(): array
    {
        return $this->beforeConsumingCallbacks;
    }

    public function getAfterConsumingCallbacks(): array
    {
        return $this->afterConsumingCallbacks;
    }

    public function shouldSendToDlq(): bool
    {
        return $this->dlq !== null;
    }

    public function shouldAssignTopicPartitions(): bool
    {
        return $this->getPartitionAssignment() !== [];
    }

    /** @return array<int, TopicPartition> */
    public function getPartitionAssignment(): array
    {
        return $this->partitionAssignment;
    }

    public function getWhenStopConsumingCallback(): ?Closure
    {
        return $this->whenStopConsuming;
    }

    /**
     * Create the librdkafka configuration with the given options and the configuration callbacks,
     * except the ones set by the caller itself.
     *
     * @param  array<string, string>  $options
     * @param  list<string>  $exceptCallbacks
     */
    public function makeConf(array $options, array $exceptCallbacks = []): Conf
    {
        $conf = new Conf;

        foreach ($options as $key => $value) {
            $conf->set($key, $value);
        }

        foreach (array_diff_key($this->callbacks, array_flip($exceptCallbacks)) as $method => $callback) {
            $conf->{$method}($callback);
        }

        return $conf;
    }

    /** librdkafka options are strings, and booleans are written "true" or "false". */
    private function normalizeOption(mixed $value): string
    {
        return is_bool($value) ? var_export($value, true) : (string) $value;
    }

    private function getSecurityProtocolOptions(): array
    {
        return $this->securityProtocol === null ? [] : ['security.protocol' => $this->securityProtocol];
    }

    private function getSaslOptions(): array
    {
        if (! $this->sasl instanceof Sasl) {
            return [];
        }

        return [
            'sasl.username' => $this->sasl->getUsername(),
            'sasl.password' => $this->sasl->getPassword(),
            'sasl.mechanisms' => $this->sasl->getMechanism(),
        ];
    }
}
