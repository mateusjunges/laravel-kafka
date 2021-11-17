<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Message\Message;
use RdKafka\Conf;

class ProducerFake
{
    private array $messages = [];

    public function __construct(
        private Config $config,
        private string $topic
    ) {
    }

    public function setConf(array $options = []): Conf
    {
        return new Conf();
    }

    public function produce(Message $message): bool
    {
        $this->messages[] = $message;

        return true;
    }

    public function getPublishedMessages(): array
    {
        return $this->messages;
    }
}
