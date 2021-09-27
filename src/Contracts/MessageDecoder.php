<?php

namespace Junges\Kafka\Contracts;

interface MessageDecoder
{
    public function decode(KafkaConsumerMessage $message): KafkaConsumerMessage;
}