<?php

namespace Junges\Kafka\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Message;
use Mockery\Exception;
use RdKafka\Conf;
use RdKafka\Producer as KafkaProducer;

class Producer
{
    private KafkaProducer $producer;

    public function __construct(
        private Config $config,
        private string $topic
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
     * @param \Junges\Kafka\Message $message
     * @return mixed
     * @throws \Exception
     */
    public function produce(Message $message): mixed
    {
        $topic = $this->producer->newTopic($this->topic);

        $topic->producev(
            partition: RD_KAFKA_PARTITION_UA,
            msgflags: 0,
            payload: $message->getPayload(),
            key: $message->getKey(),
            headers: $message->getHeaders()
        );

        $this->producer->poll(0);

        return retry(10, function () {
            $result = $this->producer->flush(1000);

            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                return true;
            }

            throw new Exception();
        });
    }
}
