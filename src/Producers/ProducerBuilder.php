<?php

namespace Junges\Kafka\Producers;

use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;

class ProducerBuilder implements CanProduceMessages
{
    use InteractsWithConfigCallbacks;

    private array $options = [];
    private KafkaProducerMessage $message;
    private MessageSerializer $serializer;
    private ?Sasl $saslConfig = null;

    public function __construct(
        private readonly string $topic,
        private ?string $broker = null,
    ) {
        /** @var KafkaProducerMessage $message */
        $message = app(KafkaProducerMessage::class);
        $this->message = $message->create($topic);
        $this->serializer = app(MessageSerializer::class);
        $this->broker = $broker ?? config('kafka.brokers');
    }

    /** Return a new Junges\Commit\ProducerBuilder instance */
    public static function create(string $topic, string $broker = null): self
    {
        return new ProducerBuilder(
            topic: $topic,
            broker: $broker ?? config('kafka.brokers')
        );
    }

    /** Sets a specific config option.*/
    public function withConfigOption(string $name, mixed $option): self
    {
        $this->options[$name] = $option;

        return $this;
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

    /** Set the message key */
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

    /** Set the message to be published. */
    public function withMessage(KafkaProducerMessage $message): self
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
    public function withSasl(Sasl $saslConfig): self
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    /** Specifies which serializer should be used. */
    public function usingSerializer(MessageSerializer $serializer): CanProduceMessages
    {
        $this->serializer = $serializer;

        return $this;
    }

    /** Disables debug. */
    public function withDebugDisabled(): self
    {
        return $this->withDebugEnabled(false);
    }

    /** Returns the topic where the message will be published. */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /** Send the given message to Kakfa.*/
    public function send(): bool
    {
        $producer = $this->build();

        return $producer->produce($this->message);
    }

    /**
     * Send a message batch to Kafka.
     *
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     */
    public function sendBatch(MessageBatch $messageBatch): int
    {
        $producer = $this->build();

        return $producer->produceBatch($messageBatch);
    }

    private function build(): Producer
    {
        $conf = new Config(
            broker: $this->broker,
            topics: [$this->getTopic()],
            securityProtocol: $this->saslConfig?->getSecurityProtocol(),
            sasl: $this->saslConfig,
            customOptions: $this->options,
            callbacks: $this->callbacks,
        );

        return app(Producer::class, [
            'config' => $conf,
            'topic' => $this->topic,
            'serializer' => $this->serializer,
        ]);
    }
}
