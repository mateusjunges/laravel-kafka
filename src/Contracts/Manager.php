<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

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

    /** Wait until every message queued on the resolved connections is delivered. */
    public function flush(): void;

    /** Get the name of the default connection. */
    public function getDefaultConnection(): string;
}
