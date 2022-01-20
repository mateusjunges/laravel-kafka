<?php

namespace Junges\Kafka\Config;

use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Contracts\Consumer;

class Config
{
    const SASL_PLAINTEXT = 'SASL_PLAINTEXT';
    const SASL_SSL = 'SASL_SSL';

    public function __construct(
        private string $broker,
        private array $topics,
        private ?string $securityProtocol = null,
        private ?int $commit = null,
        private ?string $groupId = null,
        private ?Consumer $consumer = null,
        private ?Sasl $sasl = null,
        private ?string $dlq = null,
        private int $maxMessages = -1,
        private int $maxCommitRetries = 6,
        private bool $autoCommit = true,
        private array $customOptions = []
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
            'compression.codec' => config('kafka.compression', 'snappy'),
            'group.id' => $this->groupId,
            'bootstrap.servers' => $this->broker,
        ];

        if (isset($this->autoCommit)) {
            $options['enable.auto.commit'] = $this->autoCommit === true ? 'true' : 'false';
        }

        return array_merge($options, $this->customOptions, $this->getSaslOptions());
    }

    #[Pure]
    public function getProducerOptions(): array
    {
        $config = [
            'compression.codec' => 'snappy',
            'bootstrap.servers' => $this->broker,
            'metadata.broker.list' => $this->broker,
        ];

        return array_merge($config, $this->customOptions, $this->getSaslOptions());
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
