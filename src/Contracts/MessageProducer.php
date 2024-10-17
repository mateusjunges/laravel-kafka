<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Producers\MessageBatch;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Support\Testing\Fakes\ProducerFake;

/** @internal */
interface MessageProducer extends InteractsWithConfigCallbacks
{
    /** Return a new Junges\Commit\ProducerBuilder instance. */
    public static function create(string $broker = null): self;

    /** Sets a specific config option. */
    public function withConfigOption(string $name, mixed $option): self;

    public function withTransactionalId(string $transactionalId): self;

    public function onTopic(string $topic): self;

    /** Set offset commit callback to use with consumer groups. */
    public function withOffsetCommitCb(callable $callback): self;

    /** Set rebalance callback for  use with coordinated consumer group balancing. */
    public function withRebalanceCb(callable $callback): self;

    /** Set statistics callback.  */
    public function withStatsCb(callable $callback): self;

    /** Sets configuration options. */
    public function withConfigOptions(array $options): self;

    /** Set the message headers. */
    public function withHeaders(array $headers = []): self;

    /** Set the message key. */
    public function withKafkaKey(string $key): self;

    /** Set a message array key. */
    public function withBodyKey(string $key, mixed $message): self;

    /** Set a message body. */
    public function withBody(mixed $body): self;

    /** Set the message to be published. */
    public function withMessage(ProducerMessage $message): self;

    /** Set Sasl configuration. */
    public function withSasl(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT'): self;

    public function withFlushRetries(int $retries): self;
    
    public function withFlushTimeout(int $timeoutInMs): self;

    /** Specifies which serializer should be used. */
    public function usingSerializer(MessageSerializer $serializer): self;

    /** Enables or disable debug. */
    public function withDebugEnabled(bool $enabled = true): self;

    /**
     * Send the given message to Kakfa.
     *
     * @throws \Exception
     */
    public function send(): bool;

    public function build(): Producer|ProducerFake;

    /**
     * Send a message batch to Kafka.
     *
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     * @deprecated Please use {@see Kafka::asyncPublish()} instead of batch messages.
     */
    public function sendBatch(MessageBatch $messageBatch): int;
}
