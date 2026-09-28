<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;

/**
 * Dispatched when librdkafka reports an error of a consumer or a producer that is not tied to a message, such
 * as brokers that can't be reached. librdkafka recovers from most of them by itself. Listeners must be
 * registered before the consumer or producer is created, as librdkafka logs these errors otherwise.
 */
final readonly class KafkaErrorOccurred
{
    /** @param  Consumer|null  $consumer  The consumer that reported the error, or null when it was reported by a producer. */
    public function __construct(
        public int $errorCode,
        public string $error,
        public string $connection,
        public ?Consumer $consumer = null,
    ) {}
}
