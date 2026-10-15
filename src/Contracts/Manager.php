<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Closure;
use Junges\Kafka\Connection;
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\KafkaConsumer;
use Junges\Kafka\Producers\PendingMessage;

interface Manager
{
    /** Get a Kafka connection by name, or the default connection when no name is given. */
    public function connection(?string $name = null): Connection;

    /** Start a message that is queued on the default connection's producer when sent. */
    public function publish(?string $topic = null): PendingMessage;

    /** Start a message that is flushed as soon as it is sent, using the default connection. */
    public function publishSync(?string $topic = null): PendingMessage;

    /** Start building a consumer using the default connection. */
    public function consumer(array $topics = [], ?string $groupId = null): Builder;

    /**
     * Create the builder of the given consumer class.
     *
     * @param  KafkaConsumer|class-string<KafkaConsumer>  $consumer
     */
    public function consumerFor(KafkaConsumer|string $consumer): Builder;

    /**
     * Register middlewares every consumer goes through, before the middlewares of each consumer.
     *
     * @param  list<Middleware|callable|class-string<Middleware>>|Middleware|Closure|class-string<Middleware>  $middleware
     */
    public function consumerMiddleware(array|Middleware|Closure|string $middleware): void;

    /**
     * Register a callback that configures every consumer, receiving its builder when it is created, before the
     * consumer itself is configured, so the configuration of each consumer takes precedence.
     *
     * @param  callable(Builder): mixed  $callback
     */
    public function configureConsumersUsing(callable $callback): void;

    /** Wait until every message queued on the resolved connections is delivered. */
    public function flush(): void;

    /** Get the name of the default connection. */
    public function getDefaultConnection(): string;
}
