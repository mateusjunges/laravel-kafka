<?php

namespace Junges\Kafka;

use Mockery\Exception;
use RdKafka\Producer;

class Kafka
{
    /**
     * @param Producer $producer
     */
    public function __construct(
        private Producer $producer
    ){}

    /**
     * @throws \Exception
     */
    public function publish(Message $message, string $topic, $key = null): bool
    {
        $topic = $this->producer->newTopic($topic);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $message->getPayload(), $key);

        $this->producer->poll(0);

        return retry(10, function() {
           $result = $this->producer->flush(1000);

           if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
               return true;
           }

           throw new Exception();
        });
    }
}