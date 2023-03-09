<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use RdKafka\Conf;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;
use SplDoublyLinkedList;

class Producer
{
    private readonly KafkaProducer $producer;

    public function __construct(
        private readonly Config $config,
        private readonly string $topic,
        private readonly MessageSerializer $serializer
    ) {
        $this->producer = app(KafkaProducer::class, [
            'conf' => $this->setConf($this->config->getProducerOptions()),
        ]);
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
        foreach ($messagesIterator as $message) {
            $message = $this->serializer->serialize($message);

            $this->produceMessage($topic, $message);

            $this->producer->poll(0);

            $produced++;
        }

        $this->flush();

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
    }

    /**
     * @throws CouldNotPublishMessage
     * @throws \Exception
     */
    private function flush(): mixed
    {
        $sleepMilliseconds = config('kafka.flush_retry_sleep_in_ms', 100);

        return retry(10, function () {
            $result = $this->producer->flush(1000);

            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                return true;
            }

            $message = rd_kafka_err2str($result);

            throw CouldNotPublishMessage::withMessage($message, $result);
        }, $sleepMilliseconds);
    }
}
