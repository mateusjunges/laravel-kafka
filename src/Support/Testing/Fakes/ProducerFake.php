<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\Producer;
use Junges\Kafka\Contracts\ProducerMessage;

class ProducerFake implements Producer
{
    private ?Closure $produceCallback = null;

    public function withProduceCallback(callable $callback): self
    {
        $this->produceCallback = $callback(...);

        return $this;
    }

    public function produce(ProducerMessage $message, ?MessageSerializer $serializer = null): void
    {
        if ($this->produceCallback !== null) {
            ($this->produceCallback)($message);
        }
    }

    public function flush(): void {}

    public function beginTransaction(int $timeoutInMilliseconds = 1000): void {}

    public function abortTransaction(int $timeoutInMilliseconds = 1000): void {}

    public function commitTransaction(int $timeoutInMilliseconds = 1000): void {}
}
