<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

use Closure;
use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use RdKafka\TopicPartition;

class Config
{
    final const SASL_PLAINTEXT = 'SASL_PLAINTEXT';
    final const SASL_SSL = 'SASL_SSL';
    final const PRODUCER_ONLY_CONFIG_OPTIONS = [
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
    final const CONSUMER_ONLY_CONFIG_OPTIONS = [
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
        private readonly int $commit = 1,
        private readonly ?string $groupId = null,
        private readonly ?Consumer $consumer = null,
        private readonly ?Sasl $sasl = null,
        private readonly ?string $dlq = null,
        private readonly int $maxMessages = -1,
        private readonly int $maxCommitRetries = 6,
        private readonly bool $autoCommit = true,
        private readonly array $customOptions = [],
        private readonly HandlesBatchConfiguration $batchConfig = new NullBatchConfig(),
        private readonly bool $stopAfterLastMessage = false,
        private readonly int $restartInterval = 1000,
        private readonly array $callbacks = [],
        private readonly array $beforeConsumingCallbacks = [],
        private readonly array $afterConsumingCallbacks = [],
        private readonly int $maxTime = 0,
        private readonly array $partitionAssignment = [],
        private readonly ?Closure $whenStopConsuming = null,
        public readonly ?int $flushRetries = null,
        public readonly ?int $flushTimeoutInMs = null,
    ) {
    }

    public function getCommit(): int
    {
        return $this->commit;
    }

    public function getMaxCommitRetries(): int
    {
        return $this->maxCommitRetries;
    }

    public function getTopics(): array
    {
        return $this->topics;
    }

    public function getConsumer(): Consumer
    {
        return $this->consumer;
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

    public function isAutoCommit(): bool
    {
        return $this->autoCommit;
    }

    public function shouldStopAfterLastMessage(): bool
    {
        return $this->stopAfterLastMessage;
    }

    public function getConsumerOptions(): array
    {
        $options = [
            'metadata.broker.list' => $this->broker,
            'auto.offset.reset' => config('kafka.offset_reset', 'latest'),
            'enable.auto.commit' => config('kafka.auto_commit', true) === true ? 'true' : 'false',
            'group.id' => $this->groupId,
            'bootstrap.servers' => $this->broker,
        ];

        if (isset($this->autoCommit)) {
            $options['enable.auto.commit'] = $this->autoCommit === true ? 'true' : 'false';
        }

        return collect(array_merge($options, $this->customOptions, $this->getSaslOptions()))
            ->reject(fn (string|int $option, string $key) => in_array($key, self::PRODUCER_ONLY_CONFIG_OPTIONS))
            ->toArray();
    }

    public function getProducerOptions(): array
    {
        $config = [
            'compression.codec' => config('kafka.compression', 'snappy'),
            'bootstrap.servers' => $this->broker,
            'metadata.broker.list' => $this->broker,
        ];

        return collect(array_merge($config, $this->customOptions, $this->getSaslOptions()))
            ->reject(fn (string|int $option, string $key) => in_array($key, self::CONSUMER_ONLY_CONFIG_OPTIONS))
            ->toArray();
    }

    public function getBatchConfig(): HandlesBatchConfiguration
    {
        return $this->batchConfig;
    }

    public function getRestartInterval(): int
    {
        return $this->restartInterval;
    }

    public function getConfigCallbacks(): array
    {
        return $this->callbacks;
    }

    #[Pure]
    private function getSaslOptions(): array
    {
        if ($this->usingSasl() && $this->sasl !== null) {
            return [
                'sasl.username' => $this->sasl->getUsername(),
                'sasl.password' => $this->sasl->getPassword(),
                'sasl.mechanisms' => $this->sasl->getMechanisms(),
                'security.protocol' => $this->sasl->getSecurityProtocol(),
            ];
        }

        return [];
    }

    private function usingSasl(): bool
    {
        return ! is_null($this->securityProtocol)
            && (strtoupper($this->securityProtocol) === static::SASL_PLAINTEXT
                || strtoupper($this->securityProtocol) === static::SASL_SSL);
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
        return $this->getPartitionAssigment() !== [];
    }

    /** @return array<int, TopicPartition> */
    public function getPartitionAssigment(): array
    {
        return $this->partitionAssignment;
    }

    public function getWhenStopConsumingCallback(): ?Closure
    {
        return $this->whenStopConsuming;
    }
}
