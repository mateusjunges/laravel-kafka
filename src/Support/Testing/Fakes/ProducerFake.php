<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Concerns\ManagesTransactions;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Producer;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Exceptions\CouldNotPublishMessageBatch;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;
use RdKafka\Conf;

class ProducerFake implements Producer
{
    use ManagesTransactions;

    private ?Closure $producerCallback = null;

    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function setConf(array $options = []): Conf
    {
        return new Conf();
    }

    public function withProduceCallback(callable $callback): self
    {
        $this->producerCallback = $callback;

        return $this;
    }

    public function produce(ProducerMessage $message): bool
    {
        if ($this->producerCallback !== null) {
            $callback = $this->producerCallback;
            $callback($message);
        }

        return true;
    }

    /** @throws \Junges\Kafka\Exceptions\CouldNotPublishMessageBatch */
    public function produceBatch(MessageBatch $messageBatch): int
    {
        if ($messageBatch->getTopicName() === '') {
            throw CouldNotPublishMessageBatch::invalidTopicName($messageBatch->getTopicName());
        }

        $produced = 0;

        if ($this->producerCallback !== null) {
            $callback = $this->producerCallback;

            /** @var Message $message */
            foreach ($messageBatch->getMessages() as $message) {
                $message->onTopic($messageBatch->getTopicName());
                $callback($message);
                $produced++;
            }
        }

        return $produced;
    }

    public function flush(): int
    {
        return 1;
    }
}
