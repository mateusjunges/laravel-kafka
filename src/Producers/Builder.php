<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Illuminate\Support\Traits\Conditionable;
use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\MessageProducer;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\ProducerMessage;

class Builder implements MessageProducer
{
    use InteractsWithConfigCallbacks;
    use Conditionable;

    private array $options = [];
    private ProducerMessage $message;
    private MessageSerializer $serializer;
    private ?Producer $producer = null;
    private string $topic = '';
    private ?Sasl $saslConfig = null;
    private readonly string $broker;
    private bool $isTransactionProducer = false;
    private int $maxTransactionRetryAttempts = 5;
    private ?int $flushRetries = null;
    private ?int $flushTimeoutInMs = null;

    public function __construct(
        ?string $broker = null,
        private readonly bool $asyncProducer = false,
    ) {
        /** @var ProducerMessage $message */
        $message = app(ProducerMessage::class);
        $this->message = $message::create();
        $this->serializer = app(MessageSerializer::class);
        $this->broker = $broker ?? config('kafka.brokers');
    }

    /** Return a new Junges\Commit\ProducerBuilder instance. */
    public static function create(string $broker = null): self
    {
        return new Builder(
            broker: $broker ?? config('kafka.brokers')
        );
    }

    public function onTopic(string $topic): self
    {
        $this->topic = $topic;
        $this->message->onTopic($topic);

        return $this;
    }

    /** Sets a specific config option. */
    public function withConfigOption(string $name, mixed $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }

    public function withTransactionalId(string $transactionalId): self
    {
        return $this->withConfigOption('transactional.id', $transactionalId);
    }

    /** Sets configuration options. */
    public function withConfigOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->withConfigOption($name, $value);
        }

        return $this;
    }

    /** Set the message headers. */
    public function withHeaders(array $headers = []): self
    {
        $this->message->withHeaders($headers);

        return $this;
    }

    /** Set the message key. */
    public function withKafkaKey(string $key): self
    {
        $this->message->withKey($key);

        return $this;
    }

    /** Set a message array key. */
    public function withBodyKey(string $key, mixed $message): self
    {
        $this->message->withBodyKey($key, $message);

        return $this;
    }

    /** Set a message body.  */
    public function withBody(mixed $body): self
    {
        $this->message->withBody($body);

        return $this;
    }

    public function transactional(int $maxRetryAttempts = 5): self
    {
        $this->isTransactionProducer = true;
        $this->maxTransactionRetryAttempts = $maxRetryAttempts;

        return $this;
    }

    /** Set the message to be published.  */
    public function withMessage(ProducerMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    /** Enables or disable debug. */
    public function withDebugEnabled(bool $enabled = true): self
    {
        if ($enabled) {
            $this->withConfigOptions([
                'log_level' => LOG_DEBUG,
                'debug' => 'all',
            ]);
        } else {
            unset($this->options['log_level']);
            unset($this->options['debug']);
        }

        return $this;
    }

    /** Set Sasl configuration. */
    public function withSasl(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT'): self
    {
        $this->saslConfig = new Sasl(
            username: $username,
            password: $password,
            mechanisms: $mechanisms,
            securityProtocol: $securityProtocol
        );

        return $this;
    }

    /** Specifies which serializer should be used. */
    public function usingSerializer(MessageSerializer $serializer): MessageProducer
    {
        $this->serializer = $serializer;

        return $this;
    }

    /** Disables debug. */
    public function withDebugDisabled(): self
    {
        return $this->withDebugEnabled(false);
    }

    public function withFlushRetries(int $retries): self
    {
        $this->flushRetries = $retries;

        return $this;
    }
    
    public function withFlushTimeout(int $timeoutInMs): self
    {
        $this->flushTimeoutInMs = $timeoutInMs;

        return $this;
    }

    /**
     * Send the given message to Kakfa.
     *
     * @throws \Exception
     */
    public function send(): bool
    {
        $producer = $this->build();

        if ($this->message->getTopicName() === null && $this->topic !== '') {
            $this->message->onTopic($this->topic);
        }

        return $producer->produce($this->message);
    }

    /**
     * Send a message batch to Kafka.
     *
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     * @deprecated Please use {@see Kafka::asyncPublish()} instead of batch messages.
     */
    public function sendBatch(MessageBatch $messageBatch): int
    {
        $producer = $this->build();

        if ($this->topic !== '' && $messageBatch->getTopicName() === '') {
            $messageBatch->onTopic($this->topic);
        }

        return $producer->produceBatch($messageBatch);
    }

    public function build(): Producer
    {
        if ($this->asyncProducer && $this->producer) {
            return $this->producer;
        }

        $conf = new Config(
            broker: $this->broker,
            topics: [],
            securityProtocol: $this->saslConfig?->getSecurityProtocol(),
            sasl: $this->saslConfig,
            customOptions: $this->options,
            callbacks: $this->callbacks,
            flushRetries: $this->flushRetries,
            flushTimeoutInMs: $this->flushTimeoutInMs,
        );

        $producer = app(Producer::class, [
            'config' => $conf,
            'serializer' => $this->serializer,
            'async' => $this->asyncProducer,
        ]);

        if ($this->asyncProducer) {
            $this->producer = $producer;
        }

        return $producer;
    }
}
