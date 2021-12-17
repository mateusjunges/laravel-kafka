<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Message\Message;

class ProducerBuilderFake implements CanProduceMessages
{
    private array $options = [];
    private KafkaProducerMessage $message;
    private MessageSerializer $serializer;
    private ?Sasl $saslConfig = null;
    private ?Closure $producerCallback = null;
    private string $topic = '';
    private ?string $brokers = null;

    public function __construct()
    {
        $this->message = new Message();

        $conf = new Config(
            broker: '',
            topics: [''],
            customOptions: []
        );

        $this->makeProducer($conf);
    }

    /**
     * Return a new Junges\Commit\ProducerBuilder instance.
     *
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerBuilderFake
     */
    public static function create(array $config): CanProduceMessages
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
     * @return \Junges\Kafka\Contracts\CanProduceMessages
     */
    public function withBrokers(string $brokers): CanProduceMessages
    {
        $this->brokers = $brokers;

        return $this;
    }

    /**
     * Set the topic to publish the message.
     *
     * @param string $topic
     * @return \Junges\Kafka\Contracts\CanProduceMessages
     */
    public function onTopic(string $topic): CanProduceMessages
    {
        $this->topic = $topic;

        $this->message->setTopicName($topic);

        return $this;
    }

    /**
     * Set the callback that should be used.
     *
     * @param callable $callback
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerBuilderFake
     */
    public function withProducerCallback(callable $callback): self
    {
        $this->producerCallback = $callback;

        return $this;
    }

    /**
     * Set the given configuration option with the given value on KafkaProducer.
     *
     * @param string $name
     * @param string $option
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerBuilderFake
     */
    public function withConfigOption(string $name, string $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }

    /**
     * Set config options.
     *
     * @param array $options
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerBuilderFake
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
     * @param array $headers
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerBuilderFake
     */
    public function withHeaders(array $headers): self
    {
        $this->message->withHeaders($headers);

        return $this;
    }

    /**
     * Set the message key.
     *
     * @param string $key
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerBuilderFake
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
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerBuilderFake
     */
    public function withBodyKey(string $key, mixed $message): self
    {
        $this->message->withBodyKey($key, $message);

        return $this;
    }

    /**
     * Set the entire message.
     *
     * @param Message $message
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerBuilderFake
     */
    public function withMessage(Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Enable or disable kafka debug.
     *
     * @param bool $enabled
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerBuilderFake
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
     * Get the kafka topic to be used.
     *
     * @return string
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * Get the published message.
     *
     * @return \Junges\Kafka\Message\Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * Set the Sasl configuration.
     *
     * @param \Junges\Kafka\Config\Sasl $saslConfig
     * @return \Junges\Kafka\Contracts\CanProduceMessages
     */
    public function withSasl(Sasl $saslConfig): CanProduceMessages
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    /**
     * Specify which class should be used to serialize messages.
     *
     * @param \Junges\Kafka\Contracts\MessageSerializer $serializer
     * @return \Junges\Kafka\Contracts\CanProduceMessages
     */
    public function usingSerializer(MessageSerializer $serializer): CanProduceMessages
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Send the message to the producer to be published on kafka.
     *
     * @return bool
     */
    public function send(): bool
    {
        if ($this->getMessage()->getTopicName() === null) {
            $this->message->setTopicName($this->getTopic());
        }

        $producer = $this->build();

        return $producer->produce($this->getMessage());
    }

    /**
     * Create a fake producer using the given config.
     *
     * @param \Junges\Kafka\Config\Config $config
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerFake
     */
    private function makeProducer(Config $config): ProducerFake
    {
        $producerFake = app(ProducerFake::class, [
            'config' => $config,
            'topic' => $this->getTopic(),
        ]);
        if ($this->producerCallback) {
            $producerFake->withProduceCallback($this->producerCallback);
        }

        return $producerFake;
    }

    /**
     * Build the producer.
     *
     * @return \Junges\Kafka\Support\Testing\Fakes\ProducerFake
     */
    private function build(): ProducerFake
    {
        $conf = new Config(
            broker: $this->brokers,
            topics: [$this->getTopic()],
            sasl: $this->saslConfig,
            customOptions: $this->options
        );

        return $this->makeProducer($conf);
    }
}
