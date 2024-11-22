<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\MessageProducer;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;

class ProducerBuilderFake implements MessageProducer
{
    use InteractsWithConfigCallbacks;

    private array $options = [];
    private ProducerMessage $message;
    private MessageSerializer $serializer;
    private ?Sasl $saslConfig = null;
    private string $topic = '';
    private ?Closure $producerCallback = null;
    private ?int $flushRetries = null;
    private ?int $flushTimeoutInMs = null;

    public function __construct(
        private readonly ?string $broker = null,
    ) {
        $this->message = new Message();

        $conf = new Config(
            broker: '',
            topics: [''],
            customOptions: []
        );

        $this->makeProducer($conf);
    }

    /** Return a new Junges\Commit\ProducerBuilder instance. */
    public static function create(string $broker = null): self
    {
        return new ProducerBuilderFake($broker);
    }

    public function onTopic(string $topic): self
    {
        $this->topic = $topic;
        $this->message->onTopic($topic);

        return $this;
    }

    public function withProducerCallback(callable $callback): self
    {
        $this->producerCallback = $callback;

        return $this;
    }

    public function withConfigOption(string $name, mixed $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }

    public function withTransactionalId(string $transactionalId): self
    {
        return $this->withConfigOption('transactional.id', $transactionalId);
    }

    /** Set config options */
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

    /** Set the message key. */
    public function withKafkaKey(string $key): self
    {
        $this->message->withKey($key);

        return $this;
    }

    /** Set a message array key.  */
    public function withBodyKey(string $key, mixed $message): self
    {
        $this->message->withBodyKey($key, $message);

        return $this;
    }

    /** Set a message body.  */
    public function withBody(mixed $body): self
    {
        $this->message->withBody($body);

        return $this;
    }

    /** Set the entire message.  */
    public function withMessage(ProducerMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    /** Enable or disable kafka debug. */
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

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function withSasl(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT'): MessageProducer
    {
        $this->saslConfig = new Sasl(
            username: $username,
            password: $password,
            mechanisms: $mechanisms,
            securityProtocol: $securityProtocol
        );

        return $this;
    }

    public function withFlushRetries(int $retries): self
    {
        $this->flushRetries = $retries;

        return $this;
    }

    public function withFlushTimeout(int $timeoutInMs): self
    {
        $this->flushTimeoutInMs = $timeoutInMs;

        return $this;
    }

    /** Specifies which serializer should be used. */
    public function usingSerializer(MessageSerializer $serializer): MessageProducer
    {
        $this->serializer = $serializer;

        return $this;
    }

    /** Send the message to the producer to be published on kafka. */
    public function send(): bool
    {
        $producer = $this->build();

        if ($this->message->getTopicName() === null && $this->topic !== '') {
            $this->message->onTopic($this->topic);
        }

        return $producer->produce($this->getMessage());
    }

    /**
     * Send a message batch to Kafka.
     * @deprecated Please use {@see Kafka::asyncPublish()} instead of batch messages.
     */
    public function sendBatch(MessageBatch $messageBatch): int
    {
        $producer = $this->build();

        if ($this->topic !== '' && $messageBatch->getTopicName() === '') {
            $messageBatch->onTopic($this->topic);
        }

        return $producer->produceBatch($messageBatch);
    }

    private function makeProducer(Config $config): ProducerFake
    {
        $producerFake = app(ProducerFake::class, [
            'config' => $config,
        ]);

        if ($this->producerCallback) {
            $producerFake->withProduceCallback($this->producerCallback);
        }

        return $producerFake;
    }

    /** Build the producer. */
    public function build(): ProducerFake
    {
        $conf = new Config(
            broker: $this->broker ?? config('kafka.brokers'),
            topics: [],
            sasl: $this->saslConfig,
            customOptions: $this->options,
            callbacks: $this->callbacks,
            flushRetries: $this->flushRetries,
            flushTimeoutInMs: $this->flushTimeoutInMs,
        );

        return $this->makeProducer($conf);
    }

    public function getProducer(): ProducerFake
    {
        return $this->build();
    }
}
