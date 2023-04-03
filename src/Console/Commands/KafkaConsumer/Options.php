<?php

namespace Junges\Kafka\Console\Commands\KafkaConsumer;

use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Config\Sasl;

class Options
{
    /**
     * @var mixed[]|null
     */
    private $topics;
    /**
     * @var string|null
     */
    private $consumer;
    /**
     * @var string|null
     */
    private $deserializer;
    /**
     * @var string|null
     */
    private $groupId;
    /**
     * @var int|null
     */
    private $commit = 1;
    /**
     * @var string|null
     */
    private $dlq;
    /**
     * @var int
     */
    private $maxMessages = -1;
    /**
     * @var string|null
     */
    private $securityProtocol = 'plaintext';
    /**
     * @var string|null
     */
    private $saslUsername;
    /**
     * @var string|null
     */
    private $saslPassword;
    /**
     * @var string|null
     */
    private $saslMechanisms;
    /**
     * @var mixed[]
     */
    private $config;

    public function __construct(array $options, array $config)
    {
        $options['topics'] = explode(",", $options['topics']);
        foreach ($options as $option => $value) {
            $this->{$option} = $value;
        }
        $this->config = $config;
        $this->saslPassword = $config['sasl']['password'];
        $this->saslUsername = $config['sasl']['username'];
        $this->saslMechanisms = $config['sasl']['mechanisms'];
    }

    public function getTopics(): array
    {
        return ! empty($this->topics) ? $this->topics : [];
    }

    public function getConsumer(): ?string
    {
        return $this->consumer;
    }

    public function getDeserializer(): ?string
    {
        return $this->deserializer;
    }

    public function getGroupId(): ?string
    {
        return strlen($this->groupId) > 1 ? $this->groupId : $this->config['groupId'];
    }

    public function getCommit(): ?string
    {
        return $this->commit;
    }

    public function getDlq(): ?string
    {
        return strlen($this->dlq) > 1 ? $this->dlq : null;
    }

    public function getMaxMessages(): int
    {
        return $this->maxMessages >= 1 ? $this->maxMessages : -1;
    }

    public function getSasl(): ?Sasl
    {
        if (is_null($this->saslMechanisms) || is_null($this->saslPassword) || is_null($this->saslUsername)) {
            return null;
        }
        return new Sasl(
            $this->saslUsername,
            $this->saslPassword,
            $this->saslMechanisms,
            $this->securityProtocol
        );
    }

    public function getSecurityProtocol(): ?string
    {
        return $this->securityProtocol;
    }

    public function getBroker()
    {
        return $this->config['brokers'];
    }
}
