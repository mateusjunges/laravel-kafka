<?php

namespace Junges\Kafka;

use PHPUnit\Framework\Assert as PHPUnit;
use RdKafka\Conf;

class ProducerFake
{
    private array $messages = [];

    public function __construct(
        private Config $config,
        private string $topic
    )
    {}

    public function setConf(array $options = []): Conf
    {
        return new Conf();
    }

    public function produce(Message $message): bool
    {
        $this->messages[$this->topic][] = json_encode($message->toArray());

        return true;
    }

    public function assertPublished(Message $message)
    {
        PHPUnit::assertContains($message, $this->messages);
    }

    public function getPublishedMessages(): array
    {
        return $this->messages;
    }
}