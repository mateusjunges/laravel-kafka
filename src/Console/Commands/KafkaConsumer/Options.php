<?php declare(strict_types=1);

namespace Junges\Kafka\Console\Commands\KafkaConsumer;

use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Config\Sasl;

final class Options
{
    private ?array $topics = null;
    private ?string $consumer = null;
    private ?string $deserializer = null;
    private ?string $groupId = null;
    private int $commit = 1;
    private ?string $dlq = null;
    private int $maxMessages = -1;
    private int $maxTime = 0;
    private ?string $securityProtocol = 'plaintext';
    private readonly ?string $saslUsername;
    private readonly ?string $saslPassword;
    private readonly ?string $saslMechanisms;

    #[Pure]
    public function __construct(array $options, private readonly array $config)
    {
        $options['topics'] = explode(",", (string) $options['topics']);

        foreach ($options as $option => $value) {
            $this->{$option} = $value;
        }

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
        return strlen((string) $this->groupId) > 1 ? $this->groupId : $this->config['groupId'];
    }

    public function getCommit(): int
    {
        return $this->commit;
    }

    public function getDlq(): ?string
    {
        return strlen((string) $this->dlq) > 1 ? $this->dlq : null;
    }

    public function getMaxMessages(): int
    {
        return $this->maxMessages >= 1 ? $this->maxMessages : -1;
    }

    public function getMaxTime(): int
    {
        return $this->maxTime;
    }

    #[Pure]
    public function getSasl(): ?Sasl
    {
        if (is_null($this->saslMechanisms) || is_null($this->saslPassword) || is_null($this->saslUsername)) {
            return null;
        }

        return new Sasl(
            username: $this->saslUsername,
            password: $this->saslPassword,
            mechanisms: $this->saslMechanisms,
            securityProtocol: $this->getSecurityProtocol()
        );
    }

    public function getSecurityProtocol(): ?string
    {
        $securityProtocol = strlen($this->securityProtocol ?? '') > 1
            ? $this->securityProtocol
            : $this->config['securityProtocol'];

        return $securityProtocol ?? 'plaintext';
    }

    public function getBroker()
    {
        return $this->config['brokers'];
    }
}
