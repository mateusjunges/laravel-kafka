<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Junges\Kafka\Concerns\ManagesTransactions;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\Producer as ProducerContract;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Events\BatchMessagePublished;
use Junges\Kafka\Events\MessageBatchPublished;
use Junges\Kafka\Events\MessagePublished;
use Junges\Kafka\Events\PublishingMessage;
use Junges\Kafka\Events\PublishingMessageBatch;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use Junges\Kafka\Exceptions\CouldNotPublishMessageBatch;
use Junges\Kafka\Message\Message;
use RdKafka\Conf;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;
use SplDoublyLinkedList;

class Producer implements ProducerContract
{
    use ManagesTransactions;

    private readonly KafkaProducer $producer;
    private readonly Dispatcher $dispatcher;

    public bool $transactionInitialized = false;

    public function __construct(
        private readonly Config $config,
        private readonly MessageSerializer $serializer,
        private readonly bool $async = false,
    ) {
        $this->producer = app(KafkaProducer::class, [
            'conf' => $this->getConf($this->config->getProducerOptions()),
        ]);
        $this->dispatcher = App::make(Dispatcher::class);

        if ($this->async) {
            app()->terminating(function () {
                $this->flush();
            });
        }
    }

    /** Set the Kafka Configuration. */
    private function getConf(array $options): Conf
    {
        $conf = new Conf();

        foreach ($options as $key => $value) {
            $conf->set($key, (string) $value);
        }

        foreach ($this->config->getConfigCallbacks() as $method => $callback) {
            $conf->{$method}($callback);
        }

        return $conf;
    }

    /** @inheritDoc */
    public function produce(ProducerMessage $message): bool
    {
        $this->dispatcher->dispatch(new PublishingMessage($message));

        $topic = $this->producer->newTopic($message->getTopicName());

        $message = clone $message;

        $message = $this->serializer->serialize($message);

        $this->produceMessage($topic, $message);

        $this->producer->poll(0);

        if ($this->async) {
            return true;
        }

        return $this->flush();
    }

    /**
     * @inheritDoc
     * @deprecated This will be removed in the future. Please use asyncPublish instead of batch messages.
     */
    public function produceBatch(MessageBatch $messageBatch): int
    {
        $this->assertTopicWasSetForAllBatchMessages($messageBatch);

        $messagesIterator = $messageBatch->getMessages();

        $messagesIterator->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);

        $produced = 0;

        $this->dispatcher->dispatch(new PublishingMessageBatch($messageBatch));

        foreach ($messagesIterator as $message) {
            assert($message instanceof Message);

            if ($message->getTopicName() === null) {
                $message->onTopic($messageBatch->getTopicName());
            }

            $topic = $this->producer->newTopic($message->getTopicName());

            $message = $this->serializer->serialize($message);

            $this->produceMessageBatch($topic, $message, $messageBatch->getBatchUuid());

            $this->producer->poll(0);

            $produced++;
        }

        if (! $this->async) {
            $this->flush();
        }

        $this->dispatcher->dispatch(new MessageBatchPublished($messageBatch, $produced));

        return $produced;
    }

    /** @throws CouldNotPublishMessageBatch */
    private function assertTopicWasSetForAllBatchMessages(MessageBatch $batch): void
    {
        // If the message batch has a topic set, we can return here because
        // we can use that topic as a fallback in case not all batch
        // messages have a specific topic to be used.
        if ($batch->getTopicName() !== '') {
            return;
        }

        $messagesIterator = $batch->getMessages();

        foreach ($messagesIterator as $message) {
            assert($message instanceof Message);

            // If the batch does not have a topic defined, we check if the message
            // itself has specified a topic in which it should be published.
            // If it does not, then we throw an exception.
            if ($message->getTopicName() === '' || $message->getTopicName() === null) {
                throw CouldNotPublishMessageBatch::invalidTopicName($message->getTopicName() ?? '');
            }
        }
    }

    private function produceMessage(ProducerTopic $topic, ProducerMessage $message): void
    {
        $topic->producev(
            partition: $message->getPartition(),
            msgflags: RD_KAFKA_MSG_F_BLOCK,
            payload: $message->getBody(),
            key: $message->getKey(),
            headers: $message->getHeaders()
        );

        $this->dispatcher->dispatch(new MessagePublished($message));
    }

    private function produceMessageBatch(ProducerTopic $topic, ProducerMessage $message, string $batchUuid): void
    {
        $topic->producev(
            partition: $message->getPartition(),
            msgflags: RD_KAFKA_MSG_F_BLOCK,
            payload: $message->getBody(),
            key: $message->getKey(),
            headers: $message->getHeaders()
        );

        $this->dispatcher->dispatch(new BatchMessagePublished($message, $batchUuid));
    }

    /**
     * @throws CouldNotPublishMessage
     * @throws \Exception
     */
    public function flush(): mixed
    {
        // Here we define the flush callback that is called shutting down a consumer.
        // This is called after every single message sent using Producer::send or
        // after sending all messages with Producer::sendBatch
        $flush = function () {
            $sleepMilliseconds = config('kafka.flush_retry_sleep_in_ms', 100);
            $retries = $this->config->flushRetries ?? config('kafka.flush_retries', 10);
            $timeout = $this->config->flushTimeoutInMs ?? config('kafka.flush_timeout_in_ms', 1000);

            try {
                return retry($retries, function () use ($timeout) {
                    $result = $this->producer->flush($timeout);

                    if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                        return true;
                    }

                    $message = rd_kafka_err2str($result);

                    throw CouldNotPublishMessage::withMessage($message, $result);
                }, $sleepMilliseconds);
            } catch (CouldNotPublishMessage $exception) {
                $this->dispatcher->dispatch(new \Junges\Kafka\Events\CouldNotPublishMessage(
                    $exception->getKafkaErrorCode(),
                    $exception->getMessage(),
                    $exception,
                ));

                throw  $exception;
            }
        };

        return $flush();
    }
}
