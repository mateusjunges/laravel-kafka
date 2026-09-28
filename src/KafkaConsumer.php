<?php declare(strict_types=1);

namespace Junges\Kafka;

use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Manager;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Contracts\Middleware;

/**
 * A consumer defined as a class, which can be run with "php artisan kafka:consume". Its methods
 * cover the most common options, and the configure() method gives access to the consumer builder
 * for everything else.
 */
abstract class KafkaConsumer
{
    /**
     * Get the topics to consume.
     *
     * @return list<string>
     */
    abstract public function topics(): array;

    /** Handle a consumed message. */
    abstract public function handle(ConsumerMessage $message, MessageConsumer $consumer): void;

    /** Get the connection to consume from, or null to use the default connection. */
    public function connection(): ?string
    {
        return null;
    }

    /** Get the consumer group, or null to use the group of the connection. */
    public function group(): ?string
    {
        return null;
    }

    /** Get how many times a failed message is retried before it is handled as failed. */
    public function retries(): int
    {
        return 0;
    }

    /** Get how long to wait before each retry, in milliseconds. */
    public function backoff(): int
    {
        return 0;
    }

    /** Get the dead letter queue topic, or true to use the name of the first topic followed by "-dlq". */
    public function dlq(): string|true|null
    {
        return null;
    }

    /** Determine if failed messages are skipped when there is no dead letter queue, instead of stopping the consumer. */
    public function skipFailedMessages(): bool
    {
        return false;
    }

    /**
     * Get the middlewares the messages go through before being handled.
     *
     * @return list<Middleware|callable|class-string<Middleware>>
     */
    public function middleware(): array
    {
        return [];
    }

    /** Customize the consumer builder, for anything not covered by the other methods of this class. */
    public function configure(Builder $builder): Builder
    {
        return $builder;
    }

    /** Create the builder of this consumer. */
    public function toBuilder(Manager $manager): Builder
    {
        $builder = $manager->connection($this->connection())
            ->consumer($this->topics(), $this->group())
            ->withHandler($this->handle(...))
            ->retryFailedMessages($this->retries(), $this->backoff())
            ->skipFailedMessages($this->skipFailedMessages());

        foreach ($this->middleware() as $middleware) {
            $builder->withMiddleware($middleware);
        }

        $dlq = $this->dlq();

        if ($dlq !== null) {
            $builder->withDlq($dlq === true ? null : $dlq);
        }

        return $this->configure($builder);
    }
}
