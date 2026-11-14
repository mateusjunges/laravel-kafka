<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Junges\Kafka\Concerns\ManagesTransactions;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\Producer as ProducerContract;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Events\CouldNotPublishMessage as CouldNotPublishMessageEvent;
use Junges\Kafka\Events\KafkaErrorOccurred;
use Junges\Kafka\Events\MessageDelivered;
use Junges\Kafka\Events\MessageDeliveryFailed;
use Junges\Kafka\Events\MessagePublished;
use Junges\Kafka\Events\PublishingMessage;
use Junges\Kafka\Events\StatisticsReported;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use RdKafka\Conf;
use RdKafka\Message;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;
use Throwable;

class Producer implements ProducerContract
{
    use ManagesTransactions;

    public bool $transactionInitialized = false;

    private readonly KafkaProducer $producer;

    private readonly Dispatcher $dispatcher;

    /** @var array<string, ProducerTopic> Topic handles, created once per topic. */
    private array $topics = [];

    private readonly string $messageIdKey;

    /** Whether messages were queued since the last flush. */
    private bool $hasQueuedMessages = false;

    public function __construct(
        private readonly Config $config,
        private readonly MessageSerializer $serializer,
    ) {
        $this->dispatcher = App::make(Dispatcher::class);
        $this->messageIdKey = config('kafka.message_id_key');
        $this->producer = app(KafkaProducer::class, [
            'conf' => $this->getConf($this->config->getProducerOptions()),
        ]);
    }

    /**
     * Messages are usually flushed when the application terminates. This is a last
     * resort for producers that outlive it, and it can't throw because there is
     * nothing left to handle the exception, so failures are only dispatched
     * through the CouldNotPublishMessage event.
     */
    public function __destruct()
    {
        try {
            $this->flush();
        } catch (Throwable) {
        }
    }

    /** {@inheritDoc} */
    public function produce(ProducerMessage $message, ?MessageSerializer $serializer = null): void
    {
        $this->dispatcher->dispatch(new PublishingMessage($message, $this->config->getConnectionName()));

        $topic = $this->topics[$message->getTopicName()] ??= $this->producer->newTopic($message->getTopicName());

        $message = ($serializer ?? $this->serializer)->serialize(clone $message);

        $this->produceMessage($topic, $message);
        $this->hasQueuedMessages = true;

        $this->producer->poll(0);
    }

    /** {@inheritDoc} */
    public function flush(): void
    {
        if (! $this->hasQueuedMessages) {
            return;
        }

        try {
            retry($this->config->flushRetries, function () {
                $result = $this->producer->flush($this->config->flushTimeoutInMs);

                if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                    throw CouldNotPublishMessage::withMessage(rd_kafka_err2str($result), $result);
                }
            }, $this->config->flushRetrySleepInMs);
        } catch (CouldNotPublishMessage $exception) {
            $this->dispatcher->dispatch(new CouldNotPublishMessageEvent(
                $exception->getCode(),
                $exception->getMessage(),
                $exception,
                $this->config->getConnectionName(),
            ));

            throw $exception;
        }

        $this->hasQueuedMessages = false;
    }

    /**
     * Set the Kafka Configuration. The delivery report, statistics and error callbacks are set by the producer, to
     * dispatch events, and call the ones registered on the connection. Setting an error callback stops librdkafka
     * from logging errors, so it is only set when there is a callback or a listener for them.
     */
    private function getConf(array $options): Conf
    {
        $callbacks = $this->config->getConfigCallbacks();
        $conf = $this->config->makeConf($options, exceptCallbacks: ['setDrMsgCb', 'setStatsCb', 'setErrorCb']);

        // Delivery failures of queued messages are only reported to this callback, so they are dispatched
        // as events, before calling the delivery report callback registered on the connection, if any.
        $conf->setDrMsgCb(function (KafkaProducer $kafka, Message $message) use ($callbacks): void {
            $this->handleDeliveryReport($message);

            if (isset($callbacks['setDrMsgCb'])) {
                $callbacks['setDrMsgCb']($kafka, $message);
            }
        });

        $conf->setStatsCb(function (mixed $kafka, string $statistics, int $length) use ($callbacks): void {
            $this->handleStatistics($kafka, $statistics, $length, $callbacks['setStatsCb'] ?? null);
        });

        if (isset($callbacks['setErrorCb']) || $this->dispatcher->hasListeners(KafkaErrorOccurred::class)) {
            $conf->setErrorCb(function (mixed $kafka, int $error, string $reason) use ($callbacks): void {
                $this->handleError($kafka, $error, $reason, $callbacks['setErrorCb'] ?? null);
            });
        }

        return $conf;
    }

    private function produceMessage(ProducerTopic $topic, ProducerMessage $message): void
    {
        $topic->producev(
            partition: $message->getPartition(),
            msgflags: RD_KAFKA_MSG_F_BLOCK,
            payload: $message->getBody(),
            key: $message->getKey(),
            headers: $headers = $message->getHeaders(),
            // Delivery reports don't include the message headers, so the message
            // id is passed along as the opaque value, to be reported on failures.
            msg_opaque: $headers[$this->messageIdKey] ?? null,
        );

        $this->dispatcher->dispatch(new MessagePublished($message, $this->config->getConnectionName()));
    }

    private function handleStatistics(mixed $kafka, string $statistics, int $length, ?callable $callback): void
    {
        if ($callback !== null) {
            $callback($kafka, $statistics, $length);
        }

        $this->dispatcher->dispatch(new StatisticsReported((array) json_decode($statistics, true), $this->config->getConnectionName()));
    }

    private function handleError(mixed $kafka, int $error, string $reason, ?callable $callback): void
    {
        if ($callback !== null) {
            $callback($kafka, $error, $reason);
        }

        $this->dispatcher->dispatch(new KafkaErrorOccurred($error, $reason, $this->config->getConnectionName()));
    }

    private function handleDeliveryReport(Message $message): void
    {
        if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
            $this->dispatcher->dispatch(new MessageDelivered(
                topic: $message->topic_name,
                partition: $message->partition,
                offset: $message->offset,
                key: $message->key,
                messageIdentifier: $message->opaque ?? $message->headers[$this->messageIdKey] ?? null,
                connection: $this->config->getConnectionName(),
            ));

            return;
        }

        $this->dispatcher->dispatch(new MessageDeliveryFailed(
            topic: $message->topic_name,
            partition: $message->partition,
            key: $message->key,
            payload: $message->payload,
            headers: $message->headers ?? [],
            errorCode: $message->err,
            error: $message->errstr(),
            messageIdentifier: $message->opaque ?? $message->headers[$this->messageIdKey] ?? null,
            connection: $this->config->getConnectionName(),
        ));
    }
}
