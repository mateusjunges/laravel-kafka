<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Junges\Kafka\Commit\DefaultCommitterFactory;
use Junges\Kafka\Concerns\ProcessesMessages;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\Contracts\CommitterFactory;
use Junges\Kafka\Contracts\Consumer as ConsumerContract;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\ContextAware;
use Junges\Kafka\Contracts\Logger;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\Producer as ProducerContract;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Events\ConsumerStarting;
use Junges\Kafka\Events\ConsumerStopped;
use Junges\Kafka\Events\KafkaErrorOccurred;
use Junges\Kafka\Events\MessageSentToDLQ;
use Junges\Kafka\Events\OffsetCommitFailed;
use Junges\Kafka\Events\OffsetsCommitted;
use Junges\Kafka\Events\PartitionsAssigned;
use Junges\Kafka\Events\PartitionsRevoked;
use Junges\Kafka\Events\StartedConsumingMessage;
use Junges\Kafka\Events\StatisticsReported;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Factory;
use Junges\Kafka\Message\Serializers\NullSerializer;
use Junges\Kafka\MessageCounter;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Support\InfiniteTimer;
use Junges\Kafka\Support\Timer;
use LogicException;
use RdKafka\Conf;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\KafkaConsumerTopic;
use RdKafka\Message;
use RdKafka\TopicPartition;
use Throwable;

class Consumer implements ConsumerContract
{
    use ProcessesMessages;

    /** The cache key where the "kafka:restart-consumers" command stores the time consumers were asked to restart. */
    public const string RESTART_CACHE_KEY = 'laravel-kafka:consumer:restart';

    /** The configuration callbacks the consumer sets itself, calling the ones registered for them. */
    private const array OWN_CONFIG_CALLBACKS = ['setRebalanceCb', 'setStatsCb', 'setOffsetCommitCb', 'setErrorCb'];

    private const array IGNORABLE_CONSUMER_ERRORS = [
        RD_KAFKA_RESP_ERR__PARTITION_EOF,
        RD_KAFKA_RESP_ERR__TRANSPORT,
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT,
        RD_KAFKA_RESP_ERR__TIMED_OUT,
        RD_KAFKA_RESP_ERR_UNKNOWN_TOPIC_OR_PART,
    ];

    private const array CONSUME_STOP_EOF_ERRORS = [
        RD_KAFKA_RESP_ERR__PARTITION_EOF,
        RD_KAFKA_RESP_ERR__TIMED_OUT,
    ];

    /** How many times fetching a message is retried when Kafka times out, waiting 1 second before the first retry and twice as long before each next one. */
    private const int FETCH_RETRIES = 6;

    private const array TIMEOUT_ERRORS = [
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT,
    ];

    protected int $lastRestart = 0;

    protected Timer $restartTimer;

    private readonly Logger $logger;

    private ?KafkaConsumer $consumer = null;

    private ?ProducerContract $deadLetterQueueProducer = null;

    /** The manager whose producers are flushed before storing offsets, once it is resolved. */
    private ?Factory $producers = null;

    private readonly string $messageIdKey;

    private readonly MessageCounter $messageCounter;

    private Committer $committer;

    private readonly CommitterFactory $committerFactory;

    private bool $stopRequested = false;

    private ?StopReason $stopReason = null;

    /** @var array<int, callable|int> Signal handlers of the host process, captured before consuming and restored afterwards. */
    private array $previousSignalHandlers = [];

    /** Whether the host process had async signals enabled, captured before consuming and restored afterwards. */
    private bool $previousAsyncSignals = false;

    /** @var array<string, true> Partitions that have reached EOF, keyed by "topic-partition". */
    private array $partitionsAtEof = [];

    /**
     * Topics used to store offsets, keyed by topic name. They are created once per topic,
     * as php-rdkafka never releases the topic handles created by the consumer.
     *
     * @var array<string, KafkaConsumerTopic>
     */
    private array $offsetStoreTopics = [];

    private readonly Dispatcher $dispatcher;

    public function __construct(private readonly Config $config, private readonly MessageDeserializer $deserializer, ?CommitterFactory $committerFactory = null)
    {
        $this->logger = app(Logger::class);
        $this->messageCounter = new MessageCounter($config->getMaxMessages());

        $this->committerFactory = $committerFactory ?? new DefaultCommitterFactory;
        $this->dispatcher = App::make(Dispatcher::class);
        $this->messageIdKey = config('kafka.message_id_key');
    }

    /**
     * Get the key of the cache entry storing the time consumers were asked to restart, either every consumer
     * or the consumers with the given name.
     */
    public static function restartCacheKey(?string $consumer = null): string
    {
        return $consumer === null ? self::RESTART_CACHE_KEY : self::RESTART_CACHE_KEY.':'.sha1($consumer);
    }

    /**
     * Consume messages from a kafka topic in loop.
     *
     * @throws Exception
     */
    public function consume(): void
    {
        $this->cancelStopConsume();
        $this->configureRestartTimer();
        $stopTimer = $this->configureStopTimer();

        if ($this->supportAsyncSignals()) {
            $this->listenForSignals();
        }

        $exception = null;

        try {
            $this->dispatcher->dispatch(new ConsumerStarting($this));

            $this->consumer = app(KafkaConsumer::class, [
                'conf' => $this->makeConf(),
            ]);
            $this->offsetStoreTopics = [];

            // The producer is only needed to forward failed messages to the dead letter
            // queue, and creating one opens broker connections and background threads
            // of its own, so it is created only when a dead letter queue is configured.
            if ($this->config->shouldSendToDlq()) {
                $this->deadLetterQueueProducer = app(Producer::class, [
                    'config' => $this->config,
                    'serializer' => new NullSerializer,
                ]);
            }

            $this->committer = $this->committerFactory->make($this->consumer, $this->config);

            // Calling `subscribe` overrides the assigned topic partitions, so we
            // should check if there are any assignment defined before calling
            // the subscribe method on the consumer. Partition assignment
            // have precedence over topic subscriptions.
            if ($this->config->shouldAssignTopicPartitions()) {
                $this->consumer->assign($this->config->getPartitionAssignment());
            } else {
                $this->consumer->subscribe($this->config->getTopics());
            }

            do {
                $this->runBeforeCallbacks();
                $this->doConsume();
                $this->runAfterConsumingCallbacks();
                $this->checkForRestart();
            } while (! $this->maxMessagesLimitReached() && ! $stopTimer->isTimedOut() && ! $this->stopRequested);

            $this->stopReason ??= $this->maxMessagesLimitReached() ? StopReason::MessageLimit : StopReason::TimeLimit;

            $this->config->getWhenStopConsumingCallback()?->__invoke();
        } catch (Throwable $throwable) {
            $exception = $throwable;

            throw $throwable;
        } finally {
            $this->closeConsumer();

            if ($this->supportAsyncSignals()) {
                $this->restoreSignalHandlers();
            }

            $this->dispatcher->dispatch(new ConsumerStopped(
                $this,
                $exception instanceof Throwable ? StopReason::Failed : $this->stopReason,
                $exception,
            ));
        }
    }

    /** @inheritdoc  */
    public function stopConsuming(): void
    {
        $this->requestStop(StopReason::Requested);
    }

    /** Will cancel the stopConsume request initiated by calling the stopConsume method */
    public function cancelStopConsume(): void
    {
        $this->stopRequested = false;
        $this->stopReason = null;
    }

    /** Count the number of messages consumed by this consumer */
    public function consumedMessagesCount(): int
    {
        return $this->messageCounter->messagesCounted();
    }

    /** {@inheritdoc} */
    public function commit(ConsumerMessage|Message|array|null $messageOrOffsets = null): void
    {
        $this->runCommit(fn () => $this->committer->commit($messageOrOffsets), $messageOrOffsets);
    }

    /** {@inheritdoc} */
    public function commitAsync(ConsumerMessage|Message|array|null $messageOrOffsets = null): void
    {
        $this->runCommit(fn () => $this->committer->commitAsync($messageOrOffsets), $messageOrOffsets);
    }

    /** Get the current partition assignment for this consumer */
    public function getAssignedPartitions(): array
    {
        if (! $this->consumer instanceof KafkaConsumer) {
            return [];
        }

        return $this->consumer->getAssignment();
    }

    /** {@inheritdoc} */
    public function pause(?array $partitions = null): void
    {
        $consumer = $this->runningConsumer();

        $consumer->pausePartitions($partitions ?? $consumer->getAssignment());
    }

    /** {@inheritdoc} */
    public function resume(?array $partitions = null): void
    {
        $consumer = $this->runningConsumer();

        $consumer->resumePartitions($partitions ?? $consumer->getAssignment());
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return $this->config->getName();
    }

    /** {@inheritdoc} */
    public function getConnectionName(): string
    {
        return $this->config->getConnectionName();
    }

    /** {@inheritdoc} */
    public function getGroupId(): ?string
    {
        return $this->config->getGroupId();
    }

    /** {@inheritdoc} */
    public function getTopics(): array
    {
        return $this->config->getTopics();
    }

    protected function configureRestartTimer(): void
    {
        $this->lastRestart = $this->getLastRestart();
        $this->restartTimer = new Timer;
        $this->restartTimer->start($this->config->getRestartInterval());
    }

    protected function checkForRestart(): void
    {
        if (! $this->restartTimer->isTimedOut()) {
            return;
        }

        $this->restartTimer->start($this->config->getRestartInterval());

        if ($this->lastRestart !== $this->getLastRestart()) {
            $this->requestStop(StopReason::Restart);
        }
    }

    /** Get the last time either every consumer or the consumers with the name of this one were asked to restart. */
    protected function getLastRestart(): int
    {
        $restarts = Cache::driver(config('kafka.cache_driver'))->many([
            self::restartCacheKey(),
            self::restartCacheKey($this->getName()),
        ]);

        return (int) max([0, ...array_values($restarts)]);
    }

    /** Stop consuming once the current message is processed. The first reason to stop is kept. */
    private function requestStop(StopReason $reason): void
    {
        $this->stopRequested = true;
        $this->stopReason ??= $reason;
    }

    private function runningConsumer(): KafkaConsumer
    {
        if (! $this->consumer instanceof KafkaConsumer) {
            throw new LogicException('Partitions can only be paused or resumed while the consumer is consuming.');
        }

        return $this->consumer;
    }

    /**
     * The consumer sets the rebalance, statistics, offset commit and error callbacks itself, to dispatch events,
     * and calls the ones registered for them. Setting an error callback stops librdkafka from logging errors, so
     * it is only set when there is a callback or a listener for them.
     */
    private function makeConf(): Conf
    {
        $callbacks = $this->config->getConfigCallbacks();
        $conf = $this->config->makeConf($this->config->getConsumerOptions(), exceptCallbacks: self::OWN_CONFIG_CALLBACKS);

        $conf->setRebalanceCb(function (KafkaConsumer $consumer, int $error, ?array $partitions = null) use ($callbacks): void {
            $this->rebalance($consumer, $error, $partitions, $callbacks['setRebalanceCb'] ?? null);
        });

        $conf->setStatsCb(function (mixed $kafka, string $statistics, int $length) use ($callbacks): void {
            $this->handleStatistics($kafka, $statistics, $length, $callbacks['setStatsCb'] ?? null);
        });

        $conf->setOffsetCommitCb(function (mixed $kafka, int $error, ?array $partitions = null) use ($callbacks): void {
            $this->handleOffsetCommit($kafka, $error, $partitions, $callbacks['setOffsetCommitCb'] ?? null);
        });

        if (isset($callbacks['setErrorCb']) || $this->dispatcher->hasListeners(KafkaErrorOccurred::class)) {
            $conf->setErrorCb(function (mixed $kafka, int $error, string $reason) use ($callbacks): void {
                $this->handleError($kafka, $error, $reason, $callbacks['setErrorCb'] ?? null);
            });
        }

        return $conf;
    }

    private function handleStatistics(mixed $kafka, string $statistics, int $length, ?callable $callback): void
    {
        if ($callback !== null) {
            $callback($kafka, $statistics, $length);
        }

        $this->dispatcher->dispatch(new StatisticsReported((array) json_decode($statistics, true), $this->getConnectionName(), $this));
    }

    /** @param list<TopicPartition>|null $partitions */
    private function handleOffsetCommit(mixed $kafka, int $error, ?array $partitions, ?callable $callback): void
    {
        if ($callback !== null) {
            $callback($kafka, $error, $partitions);
        }

        // There is nothing to commit when no message was processed since the last commit.
        match ($error) {
            RD_KAFKA_RESP_ERR_NO_ERROR => $this->dispatcher->dispatch(new OffsetsCommitted($this, $partitions ?? [])),
            RD_KAFKA_RESP_ERR__NO_OFFSET => null,
            default => $this->dispatcher->dispatch(new OffsetCommitFailed($this, $partitions ?? [], $error, rd_kafka_err2str($error))),
        };
    }

    private function handleError(mixed $kafka, int $error, string $reason, ?callable $callback): void
    {
        if ($callback !== null) {
            $callback($kafka, $error, $reason);
        }

        $this->dispatcher->dispatch(new KafkaErrorOccurred($error, $reason, $this->getConnectionName(), $this));
    }

    /**
     * A rebalance callback registered for the consumer replaces the default partition assignment. Otherwise,
     * the offsets of the assigned partitions are resolved, and with cooperative rebalancing, partitions are
     * added to and removed from the current assignment, instead of replacing the whole assignment.
     *
     * @param  list<TopicPartition>|null  $partitions
     */
    private function rebalance(KafkaConsumer $consumer, int $error, ?array $partitions, ?callable $callback): void
    {
        $cooperative = $this->config->usesCooperativeRebalancing();

        if ($error === RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
            if ($callback !== null) {
                $callback($consumer, $error, $partitions);
            } else {
                if ($this->config->getOffsetResolver() instanceof Closure) {
                    $partitions = ($this->config->getOffsetResolver())($partitions);
                }

                $cooperative ? $consumer->incrementalAssign($partitions) : $consumer->assign($partitions);

                $this->config->getPartitionsAssignedCallback()?->__invoke($partitions, $this);
            }

            $this->dispatcher->dispatch(new PartitionsAssigned($this, $partitions ?? []));
        } elseif ($error === RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
            if ($callback !== null) {
                $callback($consumer, $error, $partitions);
            } else {
                $this->config->getPartitionsRevokedCallback()?->__invoke($partitions, $this);

                $cooperative ? $consumer->incrementalUnassign($partitions) : $consumer->assign(null);
            }

            $this->dispatcher->dispatch(new PartitionsRevoked($this, $partitions ?? []));
        } elseif ($callback !== null) {
            $callback($consumer, $error, $partitions);
        }
    }

    /**
     * Messages published by the handler are flushed before committing, and a commit without any
     * offset to commit is not an error. The logger only logs Kafka messages, so errors committing
     * other offsets are only thrown.
     */
    private function runCommit(Closure $commit, ConsumerMessage|Message|array|null $messageOrOffsets): void
    {
        $this->flushProducers();

        try {
            $commit();
        } catch (Throwable $throwable) {
            if ($throwable->getCode() === RD_KAFKA_RESP_ERR__NO_OFFSET) {
                return;
            }

            if ($messageOrOffsets instanceof Message) {
                $this->logger->error($messageOrOffsets, $throwable, 'COMMIT_ERROR');
            }

            throw $throwable;
        }
    }

    private function configureStopTimer(): Timer
    {
        $stopTimer = $this->config->getMaxTime() === 0 ? new InfiniteTimer : new Timer;
        $stopTimer->start($this->config->getMaxTime() * 1000);

        return $stopTimer;
    }

    /**
     * Closing the consumer commits the offsets stored so far, when auto commit is enabled, and
     * leaves the consumer group right away, so its partitions are reassigned without waiting
     * for the session to time out. It runs while an exception may be propagating, so a
     * failure to close is reported instead of replacing that exception.
     */
    private function closeConsumer(): void
    {
        if (! $this->consumer instanceof KafkaConsumer) {
            return;
        }

        $consumer = $this->consumer;
        $this->consumer = null;
        $this->offsetStoreTopics = [];

        try {
            $consumer->close();
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }

    private function runBeforeCallbacks(): void
    {
        foreach ($this->config->getBeforeConsumingCallbacks() as $beforeConsumingCallback) {
            $beforeConsumingCallback($this);
        }
    }

    private function runAfterConsumingCallbacks(): void
    {
        foreach ($this->config->getAfterConsumingCallbacks() as $afterConsumingCallback) {
            $afterConsumingCallback($this);
        }
    }

    /**
     * Stop consuming on termination signals without taking the signals away from the host
     * process: a handler that was registered before (e.g. by a Laravel queue worker running
     * this consumer inside a job) is still invoked, and it is restored once consuming ends.
     */
    private function listenForSignals(): void
    {
        $this->previousAsyncSignals = pcntl_async_signals(true);

        foreach ([SIGQUIT, SIGTERM, SIGINT] as $signal) {
            $previousHandler = pcntl_signal_get_handler($signal);
            $this->previousSignalHandlers[$signal] = $previousHandler;

            pcntl_signal($signal, function (int $signal, mixed $signalInfo = null) use ($previousHandler): void {
                $this->requestStop(StopReason::Signal);

                if (is_callable($previousHandler)) {
                    $previousHandler($signal, $signalInfo);
                }
            });
        }
    }

    private function restoreSignalHandlers(): void
    {
        foreach ($this->previousSignalHandlers as $signal => $previousHandler) {
            pcntl_signal($signal, $previousHandler);
        }

        pcntl_async_signals($this->previousAsyncSignals);

        $this->previousSignalHandlers = [];
    }

    private function supportAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }

    /**
     * Execute the consume method on RdKafka consumer.
     *
     * @throws ConsumerException
     * @throws Exception|Throwable
     */
    private function doConsume(): void
    {
        // Only fetching the message is retried. Retrying the handling as well would fetch
        // the next message when handling fails with a timeout, for instance when a commit
        // times out, skipping the message that was being handled.
        $message = retry(
            self::FETCH_RETRIES + 1,
            fn (): Message => $this->consumer->consume($this->config->consumerTimeoutInMs),
            fn (int $attempt): int => 1000 * 2 ** ($attempt - 1),
            fn (Throwable $exception): bool => in_array($exception->getCode(), self::TIMEOUT_ERRORS, true),
        );

        $this->handleMessage($message);
    }

    private function logError(?Message $kafkaMessage, Throwable $throwable, string $prefix = 'ERROR'): void
    {
        if ($kafkaMessage instanceof Message) {
            $this->logger->error($kafkaMessage, $throwable, $prefix);
        }
    }

    /** @throws Throwable */
    private function executeMessage(Message $message): void
    {
        $consumerMessage = $this->getConsumerMessage($message);

        // Here we will dispatch an event to inform possible interested listeners that a message
        // was received and will be consumed as soon as a consumer is available to process it.
        $this->dispatcher->dispatch(new StartedConsumingMessage($consumerMessage, $this));

        try {
            $deserializedMessage = $this->deserializer->deserialize($consumerMessage);
        } catch (Throwable $throwable) {
            $deserializedMessage = null;

            // A message that can't be deserialized fails without reaching the handler.
            $this->handleFailedMessage($consumerMessage, $throwable, $message);
        }

        if ($deserializedMessage instanceof ConsumerMessage) {
            $this->processMessage($deserializedMessage, $message);
        }

        $this->flushProducers();
        $this->storeOffsetIfRequired($message);
    }

    /**
     * Messages published while handling a message are only queued. Flushing them before the offset
     * is stored, or committed by the handler, makes sure they are delivered before the consumed
     * message is committed, so they are not lost if the consumer crashes in between. Producers
     * with nothing queued return right away, so consumers that don't publish pay nothing.
     */
    private function flushProducers(): void
    {
        if (! $this->producers instanceof Factory && app()->resolved(Factory::class)) {
            $this->producers = app(Factory::class);
        }

        $this->producers?->flush();
    }

    /**
     * Send a failed message to the dead letter queue, with its original payload, key and headers. The
     * message is flushed right away, so its offset is only stored once the dead letter queue has it.
     *
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     */
    private function sendToDeadLetterQueue(ConsumerMessage $consumerMessage, Throwable $throwable, ?Message $message): void
    {
        $headers = $this->buildHeadersForDlq($message, $throwable);

        /** @var ProducerMessage $deadLetter */
        $deadLetter = app(ProducerMessage::class)
            ->onTopic($this->config->getDlq())
            ->withBody($message->payload)
            ->withKey($message->key)
            ->withHeaders($headers);

        $this->deadLetterQueueProducer->produce($deadLetter);
        $this->deadLetterQueueProducer->flush();

        $this->dispatcher->dispatch(new MessageSentToDLQ(
            $consumerMessage,
            $throwable,
            $this->config->getDlq(),
            $message->payload,
            $message->key,
            $headers,
            $this,
        ));
    }

    private function buildHeadersForDlq(Message $message, Throwable $throwable): array
    {
        $throwableHeaders['kafka_throwable_message'] = $throwable->getMessage();
        $throwableHeaders['kafka_throwable_code'] = $throwable->getCode();
        $throwableHeaders['kafka_throwable_class_name'] = $throwable::class;

        if ($throwable instanceof ContextAware) {
            $contextHeaders = $this->normalizeContext($throwable->getContext());
        }

        return array_merge($message->headers ?? [], $throwableHeaders, $contextHeaders ?? []);
    }

    /**
     * Store the offset of a processed message. librdkafka commits the stored offsets in the background,
     * every "auto.commit.interval.ms", and when the consumer is closed.
     *
     * @throws Exception
     */
    private function storeOffsetIfRequired(Message $message): void
    {
        if (! $this->config->shouldStoreOffsetsAfterProcessing()) {
            return;
        }

        $this->offsetStoreTopics[$message->topic_name] ??= $this->consumer->newTopic($message->topic_name);

        try {
            $this->offsetStoreTopics[$message->topic_name]->offsetStore($message->partition, $message->offset);
        } catch (Exception $exception) {
            // The partition was revoked while the message was processed. Its new
            // owner resumes from the last committed offset, so there is nothing to store.
            if ($exception->getCode() !== RD_KAFKA_RESP_ERR__STATE) {
                throw $exception;
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
     * @throws ConsumerException
     * @throws Throwable
     */
    private function handleMessage(Message $message): void
    {
        if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
            // Receiving a message from a partition that previously reached
            // EOF means the partition is no longer drained.
            unset($this->partitionsAtEof[$this->partitionKey($message->topic_name, $message->partition)]);

            $this->messageCounter->add();

            $this->executeMessage($message);

            return;
        }

        if ($this->config->shouldStopAfterLastMessage() && in_array($message->err, self::CONSUME_STOP_EOF_ERRORS, true)) {
            if ($message->err !== RD_KAFKA_RESP_ERR__PARTITION_EOF || $this->allAssignedPartitionsReachedEof($message)) {
                $this->requestStop(StopReason::Empty);
            }
        }

        if (! in_array($message->err, self::IGNORABLE_CONSUMER_ERRORS, true)) {
            $this->logger->error($message, null, 'CONSUMER');

            throw new ConsumerException($message->errstr(), $message->err);
        }
    }

    /**
     * Marks the partition which emitted the given EOF message as drained and
     * checks whether all partitions currently assigned to this consumer
     * have reached EOF, meaning there are no more messages to read.
     */
    private function allAssignedPartitionsReachedEof(Message $message): bool
    {
        $this->partitionsAtEof[$this->partitionKey($message->topic_name, $message->partition)] = true;

        return collect($this->consumer->getAssignment())->every(
            fn (TopicPartition $partition) => isset($this->partitionsAtEof[$this->partitionKey($partition->getTopic(), $partition->getPartition())])
        );
    }

    private function partitionKey(string $topic, int $partition): string
    {
        return $topic.'-'.$partition;
    }

    private function getConsumerMessage(Message $message): ConsumerMessage
    {
        // First, we set a new unique id that allows us to identify this message. Then
        // we create a new consumer message instance that will be passed as an arg
        // to the consumer class/closure responsible for consuming this message.
        if (! array_key_exists($this->messageIdKey, $message->headers ?? [])) {
            $message->headers[$this->messageIdKey] = Str::uuid()->toString();
        }

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

    /**
     * Normalizes context array to key => value pairs for headers.
     * Ignores entries with empty keys and non string keys or values.
     */
    private function normalizeContext(array $context): array
    {
        return array_filter(
            $context,
            fn (mixed $value, string $key) => $key !== '' && is_string($value),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
