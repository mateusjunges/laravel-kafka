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

    /**
     * @var mixed[]
     */
    private $options = [];
    /**
     * @var \Junges\Kafka\Contracts\KafkaProducerMessage
     */
    private $message;
    /**
     * @var \Junges\Kafka\Contracts\MessageSerializer
     */
    private $serializer;
    /**
     * @var \Junges\Kafka\Config\Sasl|null
     */
    private $saslConfig;
    /**
     * @var string
     */
    private $broker;
    /**
     * @var string
     */
    private $topic;
    public function __construct(string $topic, ?string $broker = null)
    {
        $this->topic = $topic;
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
    public static function create(string $topic, string $broker = null): \Junges\Kafka\Contracts\CanProduceMessages
    {
        return new ProducerBuilder(
            $topic,
            $broker ?? config('kafka.brokers')
        );
    }

    /**
     * Sets a specific config option.
     *
     * @param  string  $name
     * @param  mixed  $option
     * @return $this
     */
    public function withConfigOption(string $name, $option): \Junges\Kafka\Contracts\CanProduceMessages
    {
        $this->options[$name] = $option;

        return $this;
    }

    /**
     * Sets configuration options.
     *
     * @param  array  $options
     * @return $this
     */
    public function withConfigOptions(array $options): \Junges\Kafka\Contracts\CanProduceMessages
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
    public function withHeaders(array $headers = []): \Junges\Kafka\Contracts\CanProduceMessages
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
    public function withKafkaKey(string $key): \Junges\Kafka\Contracts\CanProduceMessages
    {
        $this->message->withKey($key);

        return $this;
    }

    /**
     * Set a message array key.
     *
     * @param string $key
     * @param mixed $message
     * @return $this
     */
    public function withBodyKey(string $key, $message): \Junges\Kafka\Contracts\CanProduceMessages
    {
        $this->message->withBodyKey($key, $message);

        return $this;
    }

    /**
     * Set the message to be published.
     *
     * @param  \Junges\Kafka\Contracts\KafkaProducerMessage  $message
     * @return $this
     */
    public function withMessage(KafkaProducerMessage $message): \Junges\Kafka\Contracts\CanProduceMessages
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Enables or disable debug.
     *
     * @param  bool  $enabled
     * @return $this
     */
    public function withDebugEnabled(bool $enabled = true): \Junges\Kafka\Contracts\CanProduceMessages
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
     * @param Sasl $saslConfig
     * @return $this
     */
    public function withSasl(Sasl $saslConfig): \Junges\Kafka\Contracts\CanProduceMessages
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    /**
     * Specifies which serializer should be used.
     *
     * @param  \Junges\Kafka\Contracts\MessageSerializer  $serializer
     * @return \Junges\Kafka\Contracts\CanProduceMessages
     */
    public function usingSerializer(MessageSerializer $serializer): CanProduceMessages
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
     *
     * @return string
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * Send the given message to Kakfa.
     *
     * @throws \Exception
     * @return bool
     */
    public function send(): bool
    {
        $producer = $this->build();

        return $producer->produce($this->message);
    }

    /**
     * Send a message batch to Kafka.
     *
     * @param  \Junges\Kafka\Producers\MessageBatch  $messageBatch
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     * @return int
     */
    public function sendBatch(MessageBatch $messageBatch): int
    {
        $producer = $this->build();

        return $producer->produceBatch($messageBatch);
    }

    private function build(): Producer
    {
        $conf = new Config($this->broker, [$this->getTopic()], ($saslConfig = $this->saslConfig) ? $saslConfig->getSecurityProtocol() : null, null, null, null, $this->saslConfig, null, -1, 6, true, $this->options, null, false, 1000, $this->callbacks);

        return app(Producer::class, [
            'config' => $conf,
            'topic' => $this->topic,
            'serializer' => $this->serializer,
        ]);
    }
}
