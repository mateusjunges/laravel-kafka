<?php

namespace Junges\Kafka\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use RdKafka\Conf;
use RdKafka\Producer as KafkaProducer;

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

        $message = $this->serializer->serialize($message);

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

        $this->producer->poll(0);

        return retry(10, function () {
            $result = $this->producer->flush(1000);

            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                return true;
            }

            throw CouldNotPublishMessage::flushError();
        });
    }
}
