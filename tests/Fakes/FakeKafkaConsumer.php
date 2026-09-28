<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\KafkaConsumer;

final class FakeKafkaConsumer extends KafkaConsumer
{
    public array $topics = ['orders'];

    /** @var list<ConsumerMessage> */
    public array $handled = [];

    /** @var list<string> */
    public array $middlewareCalls = [];

    public bool $configured = false;

    public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $this->handled[] = $message;
    }

    public function middleware(): array
    {
        return [
            function (ConsumerMessage $message, callable $next) {
                $this->middlewareCalls[] = 'middleware';

                return $next($message);
            },
        ];
    }

    public function configure(Builder $builder): Builder
    {
        $this->configured = true;

        return $builder->withOption('max.poll.interval.ms', 600000);
    }
}
