<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\KafkaConsumer;

final class FakeKafkaConsumer extends KafkaConsumer
{
    /** @var list<ConsumerMessage> */
    public array $handled = [];

    /** @var list<string> */
    public array $middlewareCalls = [];

    public bool $configured = false;

    /** @param array<string, mixed> $options Overrides the values returned by the configuration methods. */
    public function __construct(private readonly array $options = []) {}

    public function topics(): array
    {
        return ['orders'];
    }

    public function connection(): ?string
    {
        return $this->options['connection'] ?? null;
    }

    public function group(): ?string
    {
        return $this->options['group'] ?? null;
    }

    public function retries(): int
    {
        return $this->options['retries'] ?? 0;
    }

    public function backoff(): int
    {
        return $this->options['backoff'] ?? 0;
    }

    public function dlq(): string|true|null
    {
        return $this->options['dlq'] ?? null;
    }

    public function skipFailedMessages(): bool
    {
        return $this->options['skipFailedMessages'] ?? false;
    }

    public function handle(ConsumerMessage $message, Consumer $consumer): void
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
