<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Junges\Kafka\Commit\Contracts\Committer;
use Junges\Kafka\Commit\Contracts\CommitterFactory;
use Junges\Kafka\Commit\DefaultCommitterFactory;
use Junges\Kafka\Commit\NativeSleeper;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Logger;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\MessageCounter;
use Junges\Kafka\Retryable;
use Junges\Kafka\Support\Timer;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\Producer as KafkaProducer;
use Throwable;

class Consumer implements MessageConsumer
{
    private const IGNORABLE_CONSUMER_ERRORS = [
        RD_KAFKA_RESP_ERR__PARTITION_EOF,
        RD_KAFKA_RESP_ERR__TRANSPORT,
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT,
        RD_KAFKA_RESP_ERR__TIMED_OUT,
    ];

    private const CONSUME_STOP_EOF_ERRORS = [
        RD_KAFKA_RESP_ERR__PARTITION_EOF,
        RD_KAFKA_RESP_ERR__TIMED_OUT,
    ];

    private const TIMEOUT_ERRORS = [
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT,
    ];

    private const IGNORABLE_COMMIT_ERRORS = [
        RD_KAFKA_RESP_ERR__NO_OFFSET,
    ];

    private readonly Logger $logger;
    private KafkaConsumer $consumer;
    private KafkaProducer $producer;
    private readonly MessageCounter $messageCounter;
    private Committer $committer;
    private readonly Retryable $retryable;
    private readonly CommitterFactory $committerFactory;
    private bool $stopRequested = false;
    private ?Closure $whenStopConsuming = null;
    protected int $lastRestart = 0;
    protected Timer $restartTimer;

    public function __construct(private readonly Config $config, private readonly MessageDeserializer $deserializer, CommitterFactory $committerFactory = null)
    {
        $this->logger = app(Logger::class);
        $this->messageCounter = new MessageCounter($config->getMaxMessages());
        $this->retryable = new Retryable(new NativeSleeper(), 6, self::TIMEOUT_ERRORS);

        $this->committerFactory = $committerFactory ?? new DefaultCommitterFactory($this->messageCounter);
    }

    /**
     * Consume messages from a kafka topic in loop.
     *
     * @throws \RdKafka\Exception
     */
    public function consume(): void
    {
        $this->cancelStopConsume();
        $this->configureRestartTimer();

        if ($this->supportAsyncSignals()) {
            $this->listenForSignals();
        }

        $this->consumer = app(KafkaConsumer::class, [
            'conf' => $this->setConf($this->config->getConsumerOptions()),
        ]);
        $this->producer = app(KafkaProducer::class, [
            'conf' => $this->setConf($this->config->getProducerOptions()),
        ]);

        $this->committer = $this->committerFactory->make($this->consumer, $this->config);

        $this->consumer->subscribe($this->config->getTopics());

        $batchConfig = $this->config->getBatchConfig();

        if ($batchConfig->isBatchingEnabled()) {
            $batchConfig->getTimer()->start($batchConfig->getBatchReleaseInterval());
        }

        do {
            $this->retryable->retry(fn () => $this->doConsume());
            $this->checkForRestart();
        } while (! $this->maxMessagesLimitReached() && ! $this->stopRequested);

        if ($this->shouldRunStopConsumingCallback()) {
            $callback = $this->whenStopConsuming;
            $callback(...)();
        }
    }

    private function shouldRunStopConsumingCallback(): bool
    {
        return $this->whenStopConsuming !== null;
    }

    private function listenForSignals(): void
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGQUIT, fn () => $this->stopRequested = true);
        pcntl_signal(SIGTERM, fn () => $this->stopRequested = true);
    }

    private function supportAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }

    /** @inheritdoc  */
    public function stopConsuming(): void
    {
        $this->stopRequested = true;
    }

    /** @inheritdoc  */
    public function onStopConsuming(?Closure $onStopConsuming = null): self
    {
        $this->whenStopConsuming = $onStopConsuming;

        return $this;
    }

    /** Will cancel the stopConsume request initiated by calling the stopConsume method */
    public function cancelStopConsume(): void
    {
        $this->stopRequested = false;
    }

    /** Count the number of messages consumed by this consumer */
    public function consumedMessagesCount(): int
    {
        return $this->messageCounter->messagesCounted();
    }

    /**
     * Execute the consume method on RdKafka consumer.
     *
     * @throws ConsumerException
     * @throws \RdKafka\Exception|\Throwable
     */
    private function doConsume(): void
    {
        $message = $this->consumer->consume(config('kafka.consumer_timeout_ms', 2000));
        $this->handleMessage($message);
    }

    /** Set the consumer configuration. */
    private function setConf(array $options): Conf
    {
        $conf = new Conf();

        foreach ($options as $key => $value) {
            $conf->set($key, $value);
        }

        foreach ($this->config->getConfigCallbacks() as $method => $callback) {
            $conf->{$method}($callback);
        }

        return $conf;
    }

    /**
     * Tries to handle the message received.
     *
     * @throws \Throwable
     */
    private function executeMessage(Message $message): void
    {
        try {
            $consumedMessage = $this->getConsumerMessage($message);
            $this->config->getConsumer()->handle($this->deserializer->deserialize($consumedMessage));

            $success = true;
        } catch (Throwable $throwable) {
            $this->logger->error($message, $throwable);
            $success = $this->handleException($throwable, $message);
        }

        $this->commit($message, $success);
    }

    /**
     * Handles batch of consumed messages by checking two conditions:
     * 1) if current batch size is greater than or equals to batch size limit
     * 2) if batch release interval is timed out and current batch size greater than zero
     *
     * @throws Throwable
     */
    private function handleBatch(): void
    {
        $batchConfig = $this->config->getBatchConfig();

        $executeBatchCallback = function () use ($batchConfig) {
            $this->executeBatch($batchConfig->getBatchRepository()->getBatch());
            $batchConfig->getBatchRepository()->reset();
        };

        match (true) {
            $batchConfig->getBatchRepository()->getBatchSize() >= $batchConfig->getBatchSizeLimit(),
            $batchConfig->getTimer()->isTimedOut() && $batchConfig->getBatchRepository()->getBatchSize() > 0 => $executeBatchCallback(),
            default => null
        };

        if ($batchConfig->getTimer()->isTimedOut()) {
            $batchConfig->getTimer()->start($batchConfig->getBatchReleaseInterval());
        }
    }

    /**
     * Tries to handle received batch of messages
     *
     * @throws Throwable
     */
    private function executeBatch(Collection $collection): void
    {
        try {
            $consumedMessages = $collection
                ->map(
                    fn (Message $message) =>
                        $this->deserializer->deserialize($this->getConsumerMessage($message))
                );

            $this->config->getBatchConfig()->getConsumer()->handle($consumedMessages);

            $collection->each(fn (Message $message) => $this->commit($message, true));
        } catch (Throwable $throwable) {
            $collection->each(function (Message $message) use ($throwable) {
                $this->logger->error($message, $throwable);

                $this->commit($message, $this->handleException($throwable, $message));
            });
        }
    }

    /** Handle exceptions while consuming messages. */
    private function handleException(Throwable $exception, Message|ConsumerMessage $message): bool
    {
        try {
            $this->config->getConsumer()->failed(
                $message->payload,
                $this->config->getTopics()[0],
                $exception
            );

            return true;
        } catch (Throwable $throwable) {
            if ($exception !== $throwable) {
                $this->logger->error($message, $throwable, 'HANDLER_EXCEPTION');
            }

            report($throwable);

            return false;
        }
    }

    /** Send a message to the Dead Letter Queue. */
    private function sendToDlq(Message $message): void
    {
        $topic = $this->producer->newTopic($this->config->getDlq());
        $topic->producev(
            partition: RD_KAFKA_PARTITION_UA,
            msgflags: 0,
            payload: $message->payload,
            key: $this->config->getConsumer()->producerKey($message),
            headers: $message->headers ?? []
        );

        if (method_exists($this->producer, 'flush')) {
            $this->producer->flush(12000);
        }
    }

    /** @throws \Throwable */
    private function commit(Message $message, bool $success): void
    {
        try {
            if (! $success && ! is_null($this->config->getDlq())) {
                $this->sendToDlq($message);
                $this->committer->commitDlq($message);

                return;
            }

            $this->committer->commitMessage($message, $success);
        } catch (Throwable $throwable) {
            if (! in_array($throwable->getCode(), self::IGNORABLE_COMMIT_ERRORS, true)) {
                $this->logger->error($message, $throwable, 'MESSAGE_COMMIT');

                throw $throwable;
            }
        }
    }

    /** Determine if the max message limit is reached. */
    private function maxMessagesLimitReached(): bool
    {
        return $this->messageCounter->maxMessagesLimitReached();
    }

    /**
     * Handle the message.
     *
     * @throws \Junges\Kafka\Exceptions\ConsumerException
     * @throws \Throwable
     */
    private function handleMessage(Message $message): void
    {
        $batchConfig = $this->config->getBatchConfig();

        if (RD_KAFKA_RESP_ERR_NO_ERROR === $message->err) {
            $this->messageCounter->add();

            if ($batchConfig->isBatchingEnabled()) {
                $batchConfig->getBatchRepository()->push($message);

                $this->handleBatch();

                return;
            }

            $this->executeMessage($message);

            return;
        }

        if ($batchConfig->isBatchingEnabled()) {
            $this->handleBatch();
        }

        if ($this->config->shouldStopAfterLastMessage() && in_array($message->err, self::CONSUME_STOP_EOF_ERRORS, true)) {
            $this->stopConsuming();
        }

        if (! in_array($message->err, self::IGNORABLE_CONSUMER_ERRORS, true)) {
            $this->logger->error($message, null, 'CONSUMER');

            throw new ConsumerException($message->errstr(), $message->err);
        }
    }

    private function getConsumerMessage(Message $message): ConsumerMessage
    {
        return app(ConsumerMessage::class, [
            'topicName' => $message->topic_name,
            'partition' => $message->partition,
            'headers' => $message->headers ?? [],
            'body' => $message->payload,
            'key' => $message->key,
            'offset' => $message->offset,
            'timestamp' => $message->timestamp,
        ]);
    }

    protected function configureRestartTimer(): void
    {
        $this->lastRestart = $this->getLastRestart();
        $this->restartTimer = new Timer();
        $this->restartTimer->start($this->config->getRestartInterval());
    }

    protected function checkForRestart(): void
    {
        if (! $this->restartTimer->isTimedOut()) {
            return;
        }

        $this->restartTimer->start($this->config->getRestartInterval());
        if ($this->lastRestart !== $this->getLastRestart()) {
            $this->stopRequested = true;
        }
    }

    protected function getLastRestart(): int
    {
        return Cache::get('laravel-kafka:consumer:restart', 0);
    }
}
