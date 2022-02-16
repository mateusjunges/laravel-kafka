<?php

namespace Junges\Kafka\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;

class ProducerBuilder implements CanProduceMessages
{
    private array $options = [];
    private KafkaProducerMessage $message;
    private MessageSerializer $serializer;
    private ?Sasl $saslConfig = null;
    private ?string $topic = null;
    private ?string $brokers;

    public function __construct()
    {
        /** @var KafkaProducerMessage $message */
        $message = app(KafkaProducerMessage::class);
        $this->message = $message->create();
        $this->serializer = app(MessageSerializer::class);
        $this->brokers = $broker ?? config('kafka.brokers');
    }

    /**
     * Return a new Junges\Commit\ProducerBuilder instance
     *
     * @param array $config
     * @return \Junges\Kafka\Producers\ProducerBuilder
     */
    public static function create(array $config): self
    {
        return (new static())
            ->withBrokers($config['brokers'])
            ->withDebugEnabled($config['debug'])
            ->withConfigOption('compression.codec', $config['compression'])
            ->withConfigOptions($config['options']);
    }

    /**
     * Set the brokers to be used.
     *
     * @param string $brokers
     * @return \Junges\Kafka\Producers\ProducerBuilder
     */
    public function withBrokers(string $brokers): self
    {
        $this->brokers = $brokers;

        return $this;
    }

    /**
     * Set the topic to publish the message.
     *
     * @param string $topic
     * @return \Junges\Kafka\Producers\ProducerBuilder
     */
    public function onTopic(string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * Set the given configuration option with the given value on KafkaProducer.
     *
     * @param string $name
     * @param string $option
     * @return \Junges\Kafka\Producers\ProducerBuilder
     */
    public function withConfigOption(string $name, string $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }

    /**
     * Sets the given configuration options based on given key/value array.
     *
     * @param array $options
     * @return \Junges\Kafka\Producers\ProducerBuilder
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
     * @return \Junges\Kafka\Producers\ProducerBuilder
     */
    public function withHeaders(array $headers = []): self
    {
        $this->message->withHeaders($headers);

        return $this;
    }

    /**
     * Set the message key.
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
     * Set a message array key.
     *
     * @param string $key
     * @param mixed $message
     * @return \Junges\Kafka\Producers\ProducerBuilder
     */
    public function withBodyKey(string $key, mixed $message): self
    {
        $this->message->withBodyKey($key, $message);

        return $this;
    }

    /**
     * Sets the entire Message to be produced.
     *
     * @param KafkaProducerMessage $message
     * @return \Junges\Kafka\Producers\ProducerBuilder
     */
    public function withMessage(KafkaProducerMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Enables debug.
     *
     * @param bool $enabled
     * @return \Junges\Kafka\Producers\ProducerBuilder
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
     * @return \Junges\Kafka\Producers\ProducerBuilder
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
     * Specify which class should be used to serialize messages.
     *
     * @param MessageSerializer $serializer
     * @return CanProduceMessages
     */
    public function usingSerializer(MessageSerializer $serializer): CanProduceMessages
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Disables debug on kafka producer.
     *
     * @return \Junges\Kafka\Producers\ProducerBuilder
     */
    public function withDebugDisabled(): self
    {
        return $this->withDebugEnabled(false);
    }

    /**
     * Returns the message where the message should be published.
     *
     * @return string
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * Produces the message on Kafka.
     *
     * @return bool
     */
    public function send(): bool
    {
        $producer = $this->build();

        return $producer->produce($this->message);
    }

    /**
     * Build the Producer with the specified configuration options.
     *
     * @return \Junges\Kafka\Producers\Producer
     */
    private function build(): Producer
    {
        $conf = new Config(
            broker: $this->brokers,
            topics: [$this->getTopic()],
            securityProtocol: $this->saslConfig?->getSecurityProtocol(),
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
