<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface Handler
{
    public function __invoke(KafkaConsumerMessage $message): void;
}