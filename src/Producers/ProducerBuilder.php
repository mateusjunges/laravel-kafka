<?php

namespace Junges\Kafka\Producers;

use InvalidArgumentException;
use Junges\Kafka\Config\Cluster;
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
    private string $broker;
    private ?Cluster $cluster = null;

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

    public function withConfigOption(string $name, string $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }

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
     * @return ProducerBuilder
     */
    public function withBodyKey(string $key, mixed $message): self
    {
        $this->message->withBodyKey($key, $message);

        return $this;
    }

    public function withMessage(KafkaProducerMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

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
     * @param Sasl $saslConfig
     * @return $this
     */
    public function withSasl(Sasl $saslConfig): self
    {
        $this->saslConfig = $saslConfig;

        return $this;
    }

    public function usingSerializer(MessageSerializer $serializer): CanProduceMessages
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function withDebugDisabled(): self
    {
        return $this->withDebugEnabled(false);
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function send(): bool
    {
        $producer = $this->build();

        return $producer->produce($this->message);
    }

    private function build(): Producer
    {
        if ($this->isUsingCluster()) {
            $this->buildClusterConfiguration();
        }

        $conf = new Config(
            broker: $this->cluster ? $this->cluster->getBrokers() : $this->broker,
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

    private function isUsingCluster(): bool
    {
        return $this->cluster !== null;
    }

    private function buildClusterConfiguration(): void
    {
        $this->saslConfig = $this->cluster->getSasl();

        if ($this->cluster->isDebugEnabled()) {
            $this->withDebugEnabled();
        }

        $this->withConfigOption('compression.codec', $this->cluster->getCompression());
        $this->broker = $this->cluster->getBrokers();
    }
}
