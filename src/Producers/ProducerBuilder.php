<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\MessageProducer;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;

class ProducerBuilder implements MessageProducer
{
    use InteractsWithConfigCallbacks;

    private array $options = [];
    private ProducerMessage $message;
    private MessageSerializer $serializer;
    private ?Sasl $saslConfig = null;
    private readonly string $broker;

    public function __construct(
        private readonly string $topic,
        ?string $broker = null,
    ) {
        /** @var ProducerMessage $message */
        $message = app(ProducerMessage::class);
        $this->message = $message->create($topic);
        $this->serializer = app(MessageSerializer::class);
        $this->broker = $broker ?? config('kafka.brokers');
    }

    /**
     * Return a new Junges\Commit\ProducerBuilder instance
     * @param string|null $broker
     * @return static
     */
    public static function create(string $topic, string $broker = null): self
    {
        return new ProducerBuilder(
            topic: $topic,
            broker: $broker ?? config('kafka.brokers')
        );
    }

    /**
     * Sets a specific config option.
     *
     * @return $this
     */
    public function withConfigOption(string $name, mixed $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }

    /**
     * Sets configuration options.
     *
     * @return $this
     */
    public function withConfigOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->withConfigOption($name, $value);
        }

        return $this;
    }

    /**
     * Set the message headers.
     *
     * @return $this
     */
    public function withHeaders(array $headers = []): self
    {
        $this->message->withHeaders($headers);

        return $this;
    }

    /**
     * Set the message key.
     *
     * @return $this
     */
    public function withKafkaKey(string $key): self
    {
        $this->message->withKey($key);

        return $this;
    }

    /**
     * Set a message array key.
     *
     * @return $this
     */
    public function withBodyKey(string $key, mixed $message): self
    {
        $this->message->withBodyKey($key, $message);

        return $this;
    }

    /**
     * Set the message to be published.
     *
     * @return $this
     */
    public function withMessage(ProducerMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Enables or disable debug.
     *
     * @return $this
     */
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

    /**
     * Set Sasl configuration.
     *
     * @return $this
     */
    public function withSasl(Sasl $saslConfig): self
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    /**
     * Specifies which serializer should be used.
     */
    public function usingSerializer(MessageSerializer $serializer): MessageProducer
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Disables debug.
     *
     * @return $this
     */
    public function withDebugDisabled(): self
    {
        return $this->withDebugEnabled(false);
    }

    /**
     * Returns the topic where the message will be published.
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * Send the given message to Kakfa.
     *
     * @throws \Exception
     */
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
