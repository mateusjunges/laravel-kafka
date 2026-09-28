<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Connection;
use Junges\Kafka\Contracts\Producer;

class FakeConnection extends Connection
{
    /**
     * @param  Closure(\Junges\Kafka\Contracts\ProducerMessage): void  $recordPublishedMessage
     * @param  Closure(): array<int, \Junges\Kafka\Contracts\ConsumerMessage>  $messagesToConsume
     */
    public function __construct(
        ConnectionConfig $config,
        private readonly Closure $recordPublishedMessage,
        private readonly Closure $messagesToConsume,
    ) {
        parent::__construct($config);
    }

    public function consumer(array $topics = [], ?string $groupId = null): BuilderFake
    {
        return BuilderFake::create($this->getConfig(), $topics, $groupId)
            ->setMessages(($this->messagesToConsume)());
    }

    protected function makeProducer(): Producer
    {
        return (new ProducerFake)->withProduceCallback($this->recordPublishedMessage);
    }
}
