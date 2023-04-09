<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Events\BatchMessagePublished;
use Junges\Kafka\Events\MessageBatchPublished;
use Junges\Kafka\Events\MessagePublished;
use Junges\Kafka\Events\PublishingMessage;
use Junges\Kafka\Events\PublishingMessageBatch;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use RdKafka\Conf;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;
use SplDoublyLinkedList;

class Producer
{
    private readonly KafkaProducer $producer;
    private readonly Dispatcher $dispatcher;

    public function __construct(
        private readonly Config $config,
        private readonly string $topic,
        private readonly MessageSerializer $serializer
    ) {
        $this->producer = app(KafkaProducer::class, [
            'conf' => $this->setConf($this->config->getProducerOptions()),
        ]);
        $this->dispatcher = App::make(Dispatcher::class);
    }

    /** Set the Kafka Configuration. */
    public function setConf(array $options): Conf
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

    /**
     * Produce the specified message in the kafka topic.
     *
     * @return mixed
     * @throws \Exception
     */
    public function produce(ProducerMessage $message): bool
    {
        $this->dispatcher->dispatch(new PublishingMessage($message));

        $topic = $this->producer->newTopic($this->topic);

        $message = clone $message;

        $message = $this->serializer->serialize($message);

        $this->produceMessage($topic, $message);

        $this->producer->poll(0);

        return $this->flush();
    }

    /** @throws CouldNotPublishMessage  */
    public function produceBatch(MessageBatch $messageBatch): int
    {
        $topic = $this->producer->newTopic($this->topic);

        $messagesIterator = $messageBatch->getMessages();

        $messagesIterator->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);

        $produced = 0;

        $this->dispatcher->dispatch(new PublishingMessageBatch($messageBatch));
        foreach ($messagesIterator as $message) {
            $message = $this->serializer->serialize($message);

            $this->produceMessageBatch($topic, $message, $messageBatch->getBatchUuid());

            $this->producer->poll(0);

            $produced++;
        }

        $this->flush();

        $this->dispatcher->dispatch(new MessageBatchPublished($messageBatch, $produced));

        return $produced;
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
    private function flush(): mixed
    {
        $sleepMilliseconds = config('kafka.flush_retry_sleep_in_ms', 100);

        try {
            return retry(10, function () {
                $result = $this->producer->flush(1000);

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
    }
}
