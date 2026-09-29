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
        ?Closure $configureConsumer = null,
    ) {
        parent::__construct($config, $configureConsumer);
    }

    protected function newConsumerBuilder(array $topics, ?string $groupId): BuilderFake
    {
        return BuilderFake::create($this->getConfig(), $topics, $groupId)
            ->setMessages(($this->messagesToConsume)());
    }

    protected function makeProducer(): Producer
    {
        return (new ProducerFake)->withProduceCallback($this->recordPublishedMessage);
    }
}
