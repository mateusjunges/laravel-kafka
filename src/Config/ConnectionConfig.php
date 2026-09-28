<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

use InvalidArgumentException;

final readonly class ConnectionConfig
{
    /**
     * @param  array<string, mixed>  $options  librdkafka options applied to both producers and consumers.
     * @param  array<string, mixed>  $producerOptions  librdkafka options applied only to producers.
     * @param  array<string, mixed>  $consumerOptions  librdkafka options applied only to consumers.
     * @param  array<string, callable>  $callbacks  librdkafka configuration callbacks, keyed by the \RdKafka\Conf setter name.
     */
    public function __construct(
        public string $name,
        public string $brokers,
        public ?string $securityProtocol = null,
        public ?Sasl $sasl = null,
        public array $options = [],
        public array $producerOptions = [],
        public array $consumerOptions = [],
        public ?string $groupId = null,
        public bool $autoCommit = true,
        public int $consumerTimeoutInMs = 2000,
        public int $flushTimeoutInMs = 1000,
        public int $flushRetries = 10,
        public int $flushRetrySleepInMs = 100,
        public array $callbacks = [],
    ) {}

    /** Create the connection configuration from a "kafka.connections.*" config array. */
    public static function fromArray(string $name, array $config): self
    {
        if (blank($config['brokers'] ?? null)) {
            throw new InvalidArgumentException("The Kafka connection [{$name}] does not have any brokers configured.");
        }

        $securityProtocol = $config['security_protocol'] ?? null;
        $producer = $config['producer'] ?? [];
        $consumer = $config['consumer'] ?? [];

        return new self(
            name: $name,
            brokers: (string) $config['brokers'],
            securityProtocol: $securityProtocol,
            sasl: self::makeSasl($config['sasl'] ?? [], $securityProtocol),
            options: $config['options'] ?? [],
            producerOptions: $producer['options'] ?? [],
            consumerOptions: $consumer['options'] ?? [],
            groupId: $consumer['group_id'] ?? null,
            autoCommit: filter_var($consumer['auto_commit'] ?? true, FILTER_VALIDATE_BOOL),
            consumerTimeoutInMs: (int) ($consumer['timeout_ms'] ?? 2000),
            flushTimeoutInMs: (int) ($producer['flush_timeout_ms'] ?? 1000),
            flushRetries: (int) ($producer['flush_retries'] ?? 10),
            flushRetrySleepInMs: (int) ($producer['flush_retry_sleep_ms'] ?? 100),
        );
    }

    /** Return a copy of this configuration using the given librdkafka configuration callbacks. */
    public function withCallbacks(array $callbacks): self
    {
        return new self(...[...get_object_vars($this), 'callbacks' => $callbacks]);
    }

    private static function makeSasl(array $sasl, ?string $securityProtocol): ?Sasl
    {
        if (blank($sasl['username'] ?? null)) {
            return null;
        }

        return new Sasl(
            username: (string) $sasl['username'],
            password: (string) ($sasl['password'] ?? ''),
            mechanisms: (string) ($sasl['mechanisms'] ?? 'PLAIN'),
            securityProtocol: $securityProtocol ?? 'SASL_PLAINTEXT',
        );
    }
}
