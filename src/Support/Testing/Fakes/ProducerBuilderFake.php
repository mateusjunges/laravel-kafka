<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;

class ProducerBuilderFake implements CanProduceMessages
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
     * @var \Closure|null
     */
    private $producerCallback;
    /**
     * @var string
     */
    private $topic;
    /**
     * @var string|null
     */
    private $broker;
    public function __construct(string $topic, ?string $broker = null)
    {
        $this->topic = $topic;
        $this->broker = $broker;
        $this->message = new Message($topic);
        $conf = new Config(
            '',
            [''],
            null,
            null,
            null,
            null,
            null,
            null,
            -1,
            6,
            true,
            []
        );
        $this->makeProducer($conf);
    }

    /**
     * Return a new Junges\Commit\ProducerBuilder instance
     * @param string $topic
     * @param string|null $broker
     * @return static
     */
    public static function create(string $topic, string $broker = null): \Junges\Kafka\Contracts\CanProduceMessages
    {
        return new ProducerBuilderFake($broker, $topic);
    }

    public function withProducerCallback(callable $callback): self
    {
        $this->producerCallback = $callback;

        return $this;
    }

    /**
     * @param mixed $option
     * @return $this
     */
    public function withConfigOption(string $name, $option): \Junges\Kafka\Contracts\CanProduceMessages
    {
        $this->options[$name] = $option;

        return $this;
    }

    /**
     * Set config options
     * @param array $options
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
     * Set the entire message.
     *
     * @param \Junges\Kafka\Contracts\KafkaProducerMessage $message
     * @return $this
     */
    public function withMessage(KafkaProducerMessage $message): \Junges\Kafka\Contracts\CanProduceMessages
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Enable or disable kafka debug.
     *
     * @param bool $enabled
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
     * Get the kafka topic to be used.
     * @return string
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function withSasl(Sasl $saslConfig): CanProduceMessages
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
     * Send a message batch to Kafka.
     *
     * @param  \Junges\Kafka\Producers\MessageBatch  $messageBatch
     * @return int
     */
    public function sendBatch(MessageBatch $messageBatch): int
    {
        $producer = $this->build();

        return $producer->produceBatch($messageBatch);
    }

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
     * @return ProducerFake
     */
    private function build(): ProducerFake
    {
        $conf = new Config($this->broker ?? config('kafka.brokers'), [$this->getTopic()], null, null, null, null, $this->saslConfig, null, -1, 6, true, $this->options, null, false, 1000, $this->callbacks);

        return $this->makeProducer($conf);
    }
}
