<?php

namespace Junges\Kafka\Contracts;

interface MessageDeserializer
{
    /**
     * Deserializes the message.
     *
     * @param KafkaConsumerMessage $message
     * @return KafkaConsumerMessage
     */
    public function deserialize(KafkaConsumerMessage $message): KafkaConsumerMessage;
}
