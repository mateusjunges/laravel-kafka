<?php

namespace Junges\Kafka\Contracts;

interface MessageSerializer
{
    /**
     * Serializes the message.
     *
     * @param KafkaProducerMessage $message
     * @return KafkaProducerMessage
     */
    public function serialize(KafkaProducerMessage $message): KafkaProducerMessage;
}
