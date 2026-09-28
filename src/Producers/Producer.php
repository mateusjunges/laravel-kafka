<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Junges\Kafka\Concerns\ManagesTransactions;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\Producer as ProducerContract;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Events\CouldNotPublishMessage as CouldNotPublishMessageEvent;
use Junges\Kafka\Events\MessageDeliveryFailed;
use Junges\Kafka\Events\MessagePublished;
use Junges\Kafka\Events\PublishingMessage;
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

    /** @var list<ProducerMessage> */
    private array $pendingMessages = [];

    private ?Closure $flushCallback = null;

    /** Whether messages were queued since the last flush. */
    private bool $hasQueuedMessages = false;

    public function __construct(
        private readonly Config $config,
        private readonly MessageSerializer $serializer,
    ) {
        $this->producer = app(KafkaProducer::class, [
            'conf' => $this->getConf($this->config->getProducerOptions()),
        ]);
        $this->dispatcher = App::make(Dispatcher::class);
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
        $this->dispatcher->dispatch(new PublishingMessage($message));

        $topic = $this->producer->newTopic($message->getTopicName());

        $message = ($serializer ?? $this->serializer)->serialize(clone $message);

        $this->produceMessage($topic, $message);
        $this->hasQueuedMessages = true;

        if ($this->flushCallback instanceof Closure) {
            $this->pendingMessages[] = $message;
        }

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
                $exception->getKafkaErrorCode(),
                $exception->getMessage(),
                $exception,
            ));

            throw $exception;
        }

        $this->hasQueuedMessages = false;

        $this->runFlushCallback();
    }

    /** {@inheritDoc} */
    public function withFlushCallback(callable $callback): self
    {
        $this->flushCallback = $callback(...);

        return $this;
    }

    /** Set the Kafka Configuration. */
    private function getConf(array $options): Conf
    {
        $conf = new Conf;

        foreach ($options as $key => $value) {
            $conf->set($key, (string) $value);
        }

        $callbacks = $this->config->getConfigCallbacks();
        $deliveryReportCallback = $callbacks['setDrMsgCb'] ?? null;
        unset($callbacks['setDrMsgCb']);

        foreach ($callbacks as $method => $callback) {
            $conf->{$method}($callback);
        }

        // Delivery failures of queued messages are only reported to this callback, so they are dispatched
        // as events, before calling the delivery report callback registered on the connection, if any.
        $conf->setDrMsgCb(function (KafkaProducer $kafka, Message $message) use ($deliveryReportCallback): void {
            $this->handleDeliveryReport($message);

            if ($deliveryReportCallback !== null) {
                $deliveryReportCallback($kafka, $message);
            }
        });

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
            msg_opaque: $headers[config('kafka.message_id_key')] ?? null,
        );

        $this->dispatcher->dispatch(new MessagePublished($message));
    }

    private function handleDeliveryReport(Message $message): void
    {
        if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
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
            messageIdentifier: $message->opaque ?? $message->headers[config('kafka.message_id_key')] ?? null,
        ));
    }

    private function runFlushCallback(): void
    {
        if ($this->pendingMessages === []) {
            return;
        }

        $pendingMessages = $this->pendingMessages;
        $this->pendingMessages = [];

        ($this->flushCallback)($pendingMessages);
    }
}
