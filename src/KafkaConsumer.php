<?php declare(strict_types=1);

namespace Junges\Kafka;

use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Manager;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Contracts\Middleware;

/**
 * A consumer defined as a class, which can be run with "php artisan kafka:consume". Its properties
 * cover the most common options, and the configure() method gives access to the consumer builder
 * for everything else.
 */
abstract class KafkaConsumer
{
    /** @var list<string> The topics to consume. */
    public array $topics = [];

    /** The connection to consume from, or null to use the default connection. */
    public ?string $connection = null;

    /** The consumer group, or null to use the group of the connection. */
    public ?string $group = null;

    /** How many times a failed message is retried before it is handled as failed. */
    public int $retries = 0;

    /** How long to wait before each retry, in milliseconds. */
    public int $backoff = 0;

    /** The dead letter queue topic, or true to use the name of the first topic followed by "-dlq". */
    public string|true|null $dlq = null;

    /** Whether failed messages are skipped when there is no dead letter queue, instead of stopping the consumer. */
    public bool $skipFailedMessages = false;

    /** Handle a consumed message. */
    abstract public function handle(ConsumerMessage $message, MessageConsumer $consumer): void;

    /**
     * Get the middlewares the messages go through before being handled.
     *
     * @return list<Middleware|callable|class-string<Middleware>>
     */
    public function middleware(): array
    {
        return [];
    }

    /** Customize the consumer builder, for anything not covered by the properties of this class. */
    public function configure(Builder $builder): Builder
    {
        return $builder;
    }

    /** Create the builder of this consumer. */
    public function toBuilder(Manager $manager): Builder
    {
        $builder = $manager->connection($this->connection)
            ->consumer($this->topics, $this->group)
            ->withHandler($this->handle(...))
            ->retryFailedMessages($this->retries, $this->backoff)
            ->skipFailedMessages($this->skipFailedMessages);

        foreach ($this->middleware() as $middleware) {
            $builder->withMiddleware($middleware);
        }

        if ($this->dlq !== null) {
            $builder->withDlq($this->dlq === true ? null : $this->dlq);
        }

        return $this->configure($builder);
    }
}
