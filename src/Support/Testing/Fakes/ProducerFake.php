<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;
use RdKafka\Conf;

class ProducerFake
{
    private array $messages = [];
    private ?Closure $producerCallback = null;

    public function __construct(
        private readonly Config $config,
        private readonly string $topic
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

    public function produce(Message $message): bool
    {
        if ($this->producerCallback !== null) {
            $callback = $this->producerCallback;
            $callback($message);
        }

        return true;
    }

    public function produceBatch(MessageBatch $messageBatch): int
    {
        $produced = 0;
        if ($this->producerCallback !== null) {
            $callback = $this->producerCallback;

            /** @var Message $message */
            foreach ($messageBatch->getMessages() as $message) {
                $message->setTopicName($this->topic);
                $callback($message);
                $produced++;
            }
        }

        return $produced;
    }
}
