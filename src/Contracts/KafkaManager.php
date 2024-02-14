<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface KafkaManager extends ConsumeMessagesFromKafka, MessagePublisher
{

}