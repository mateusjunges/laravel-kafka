<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Concerns\ManagesTransactions;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Producer;
use Junges\Kafka\Contracts\ProducerMessage;
use RdKafka\Conf;

class ProducerFake implements Producer
{
    use ManagesTransactions;

    private ?Closure $produceCallback = null;

    private ?Closure $flushCallback = null;

    private array $pendingMessages = [];

    public function __construct(
        private readonly Config $config,
    ) {}

    public function setConf(array $options = []): Conf
    {
        return new Conf;
    }

    public function withProduceCallback(callable $callback): self
    {
        $this->produceCallback = Closure::fromCallable($callback);

        return $this;
    }

    public function withFlushCallback(callable $callback): self
    {
        $this->flushCallback = Closure::fromCallable($callback);

        return $this;
    }

    public function produce(ProducerMessage $message): bool
    {
        if ($this->produceCallback !== null) {
            ($this->produceCallback)($message);
        }

        $this->pendingMessages[] = $message;

        $this->flush();

        return true;
    }

    public function flush(): int
    {
        if ($this->pendingMessages !== [] && $this->flushCallback !== null) {
            ($this->flushCallback)($this->pendingMessages);
        }

        $this->pendingMessages = [];

        return 1;
    }
}
