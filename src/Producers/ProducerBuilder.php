<?php

namespace Junges\Kafka\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Message\Message;

class ProducerBuilder implements CanProduceMessages
{
    private array $options = [];
    private KafkaProducerMessage $message;
    private MessageSerializer $serializer;
    private ?Sasl $saslConfig = null;
    private string $broker;

    public function __construct(
        private string $topic,
        ?string $broker = null,
    ) {
        /** @var KafkaProducerMessage $message */
        $message = app(KafkaProducerMessage::class);
        $this->message = $message->create($topic);
        $this->serializer = app(MessageSerializer::class);
        $this->broker = $broker ?? config('kafka.brokers');
    }

    /**
     * Return a new Junges\Commit\ProducerBuilder instance
     * @param string $topic
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
     * Set a specific configuration option on the Producer.
     *
     * @param string $name
     * @param string $option
     * @return $this
     */
    public function withConfigOption(string $name, string $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }

    /**
     * Set config options based on key/value array.
     *
     * @param array $options
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
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->message->withHeaders($headers);

        return $this;
    }

    /**
     * Set the Kafka key that should be used.
     *
     * @param string $key
     * @return $this
     */
    public function withKafkaKey(string $key): self
    {
        $this->message->withKey($key);

        return $this;
    }

    /**
     * Set a given key on the kafka message body.
     *
     * @param string $key
     * @param mixed $message
     * @return $this
     */
    public function withBodyKey(string $key, mixed $message): self
    {
        $this->message->withBodyKey($key, $message);

        return $this;
    }

    /**
     * Set the message to be published based on a Message object.
     *
     * @param Message $message
     * @return $this
     */
    public function withMessage(KafkaProducerMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Enable or disable debug on kafka producer.
     *
     * @param bool $enabled
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
     * Set the Sasl configuration.
     *
     * @param string $username
     * @param string $password
     * @param string $mechanisms
     * @param string $securityProtocol
     * @return $this
     */
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

    /**
     * Specify the class that should be used to serialize messages.
     *
     * @param MessageSerializer $serializer
     * @return $this
     */
    public function usingSerializer(MessageSerializer $serializer): CanProduceMessages
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Disables debug on kafka producer.
     *
     * @return $this
     */
    public function withDebugDisabled(): self
    {
        return $this->withDebugEnabled(false);
    }

    /**
     * Get the topic in which the message should be published.
     *
     * @return string
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * Publish the message to the kafka cluster.
     *
     * @return bool
     */
    public function send(): bool
    {
        $producer = $this->build();

        return $producer->produce($this->message);
    }

    /**
     * Build the producer with the given configuration options.
     *
     * @return Producer
     */
    private function build(): Producer
    {
        $conf = new Config(
            broker: $this->broker,
            topics: [$this->getTopic()],
            sasl: $this->saslConfig,
            customOptions: $this->options,
        );

        return app(Producer::class, [
            'config' => $conf,
            'topic' => $this->topic,
            'serializer' => $this->serializer,
        ]);
    }
}
