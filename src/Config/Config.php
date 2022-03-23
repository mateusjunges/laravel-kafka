<?php

namespace Junges\Kafka\Config;

use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Contracts\Consumer;

class Config
{
    const SASL_PLAINTEXT = 'SASL_PLAINTEXT';
    const SASL_SSL = 'SASL_SSL';
    const PRODUCER_ONLY_CONFIG_OPTIONS = [
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
    const CONSUMER_ONLY_CONFIG_OPTIONS = [
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

    private HandlesBatchConfiguration $batchConfig;

    public function __construct(
        private string $broker,
        private array $topics,
        private ?string            $securityProtocol = null,
        private ?int               $commit = null,
        private ?string            $groupId = null,
        private ?Consumer          $consumer = null,
        private ?Sasl              $sasl = null,
        private ?string            $dlq = null,
        private int                $maxMessages = -1,
        private int                $maxCommitRetries = 6,
        private bool               $autoCommit = true,
        private array              $customOptions = [],
        ?HandlesBatchConfiguration $batchConfig = null,
    ) {
        $this->batchConfig = $batchConfig ?? new NullBatchConfig();
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

    public function isAutoCommit(): bool
    {
        return $this->autoCommit;
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
            ->reject(fn ($option) => in_array($option, self::PRODUCER_ONLY_CONFIG_OPTIONS))
            ->toArray();
    }

    #[Pure]
    public function getProducerOptions(): array
    {
        $config = [
            'compression.codec' => 'snappy',
            'bootstrap.servers' => $this->broker,
            'metadata.broker.list' => $this->broker,
        ];

        return collect(array_merge($config, $this->customOptions, $this->getSaslOptions()))
            ->reject(fn ($option) => in_array($option, self::CONSUMER_ONLY_CONFIG_OPTIONS))
            ->toArray();
    }

    public function getBatchConfig(): HandlesBatchConfiguration
    {
        return $this->batchConfig;
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
        return strtoupper($this->securityProtocol) === static::SASL_PLAINTEXT
            || strtoupper($this->securityProtocol) === static::SASL_SSL;
    }
}
