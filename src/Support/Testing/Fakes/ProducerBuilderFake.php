<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Cluster;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\ProducerBuilder;

class ProducerBuilderFake implements CanProduceMessages
{
    private array $options = [];
    private KafkaProducerMessage $message;
    private MessageSerializer $serializer;
    private ?Sasl $saslConfig = null;
    private ?Closure $producerCallback = null;
    private ?Cluster $cluster = null;

    public function __construct(
        private string $topic,
        private ?string $broker = null,
    ) {
        $this->message = new Message($topic);

        $conf = new Config(
            broker: '',
            topics: [''],
            customOptions: []
        );

        $this->makeProducer($conf);
    }

    /**
     * Return a new Junges\Commit\ProducerBuilder instance
     * @param string $topic
     * @param string|null $broker
     * @return static
     */
    public static function create(string $topic, string $broker = null): self
    {
        return new ProducerBuilderFake($broker, $topic);
    }

    /**
     * Create a Cluster instance with the given cluster configuration.
     *
     * @param string $cluster
     * @return $this
     */
    public function usingCluster(string $cluster = 'default'): self
    {
        $clusterConfig = config('kafka.clusters.'.$cluster);

        if ($clusterConfig === null) {
            throw new InvalidArgumentException("Cluster [{$cluster}] is not defined.");
        }

        $this->cluster = Cluster::createFromConfig($clusterConfig);

        return $this;
    }

    public function withProducerCallback(callable $callback): self
    {
        $this->producerCallback = $callback;

        return $this;
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

    public function withSasl(Sasl $saslConfig): CanProduceMessages
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    public function usingSerializer(MessageSerializer $serializer): CanProduceMessages
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Send the message to the producer to be published on kafka.
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
        if ($this->isUsingCluster()) {
            $this->buildClusterConfiguration();
        }

        $conf = new Config(
            broker: $this->broker ?? config('kafka.brokers'),
            topics: [$this->getTopic()],
            sasl: $this->saslConfig,
            customOptions: $this->options
        );

        return $this->makeProducer($conf);
    }

    private function isUsingCluster(): bool
    {
        return $this->cluster !== null;
    }

    private function buildClusterConfiguration(): void
    {
        if ($this->cluster->isSaslEnabled()) {
            $this->saslConfig = new Sasl(
                username: $this->cluster->getSaslUsername(),
                password: $this->cluster->getSaslPassword(),
                mechanisms: $this->cluster->getSaslMechanism(),
                securityProtocol: $this->cluster->getSaslSecurityProtocol()
            );
        }

        if ($this->cluster->isDebugEnabled()) {
            $this->withDebugEnabled();
        }

        $this->withConfigOption('compression.codec', $this->cluster->getCompression());
        $this->broker = $this->cluster->getBrokers();
    }
}
