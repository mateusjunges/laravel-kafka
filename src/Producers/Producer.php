<?php

namespace Junges\Kafka\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use RdKafka\Conf;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;
use SplDoublyLinkedList;

class Producer
{
    private KafkaProducer $producer;

    public function __construct(
        private Config $config,
        private string $topic,
        private MessageSerializer $serializer
    ) {
        $this->producer = app(KafkaProducer::class, [
            'conf' => $this->setConf($this->config->getProducerOptions()),
        ]);
    }

    /**
     * Set the Kafka Configuration.
     *
     * @param array $options
     * @return \RdKafka\Conf
     */
    public function setConf(array $options): Conf
    {
        $conf = new Conf();

        foreach ($options as $key => $value) {
            $conf->set($key, $value);
        }

        return $conf;
    }

    /**
     * Produce the specified message in the kafka topic.
     *
     * @param KafkaProducerMessage $message
     * @return mixed
     * @throws \Exception
     */
    public function produce(KafkaProducerMessage $message): bool
    {
        $topic = $this->producer->newTopic($this->topic);

        $message = clone $message;

        $message = $this->serializer->serialize($message);

        $this->produceMessage($topic, $message);

        $this->producer->poll(0);

        return $this->flush();
    }

    /**
     * @throws CouldNotPublishMessage
     */
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

    private function produceMessage(ProducerTopic $topic, KafkaProducerMessage $message): void
    {
        if (method_exists($topic, 'producev')) {
            $topic->producev(
                partition: $message->getPartition(),
                msgflags: RD_KAFKA_MSG_F_BLOCK,
                payload: $message->getBody(),
                key: $message->getKey(),
                headers: $message->getHeaders()
            );
        } else {
            $topic->produce(
                partition: $message->getPartition(),
                msgflags: 0,
                payload: $message->getBody(),
                key: $message->getKey()
            );
        }
    }

    /**
     * @throws CouldNotPublishMessage
     * @throws \Exception
     */
    private function flush(): mixed
    {
        return retry(10, function () {
            $result = $this->producer->flush(1000);

            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                return true;
            }

            throw CouldNotPublishMessage::flushError();
        });
    }
}
