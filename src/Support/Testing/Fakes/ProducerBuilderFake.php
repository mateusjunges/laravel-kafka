<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\MessageEncoder;
use Junges\Kafka\Message\Message;

class ProducerBuilderFake implements CanProduceMessages
{
    private array $options = [];
    private Message $message;
    private ProducerFake $producerFake;
    private MessageEncoder $encoder;

    public function __construct(
        private string $broker,
        private string $topic,
    ) {
        $this->message = new Message($topic);

        $conf = new Config(
            broker: '',
            topics: [''],
            customOptions: []
        );

        $this->producerFake = app(ProducerFake::class, [
            'config' => $conf,
            'topic' => $this->topic,
        ]);
    }

    /**
     * Return a new Junges\Commit\ProducerBuilder instance
     * @param string $broker
     * @param string $topic
     * @return static
     */
    public static function create(string $broker, string $topic): self
    {
        return new ProducerBuilderFake($broker, $topic);
    }

    public function withConfigOption(string $name, string $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }

    /**
     * Set config options
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
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->message->withHeaders($headers);

        return $this;
    }

    /**
     * Set the message key.
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
     * @param string $key
     * @param mixed $message
     * @return ProducerBuilderFake
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
     * @return $this
     */
    public function withMessage(Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Enable or disable kafka debug.
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

    /**
     * Send the message to the producer to be published on kafka.
     * @return bool
     */
    public function send(): bool
    {
        $producer = $this->build();

        return $producer->produce($this->getMessage());
    }

    public function getProducer(): ProducerFake
    {
        return $this->producerFake;
    }

    /**
     * Build the producer.
     *
     * @return ProducerFake
     */
    private function build(): ProducerFake
    {
        $conf = new Config(
            broker: $this->broker,
            topics: [$this->getTopic()],
            customOptions: $this->options
        );

        $this->producerFake = app(ProducerFake::class, [
            'config' => $conf,
            'topic' => $this->topic,
        ]);

        return $this->producerFake;
    }

    public function usingEncoder(MessageEncoder $encoder): CanProduceMessages
    {
        $this->encoder = $encoder;

        return $this;
    }
}
