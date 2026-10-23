<?php declare(strict_types=1);

namespace Junges\Kafka;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Logger as LoggerContract;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\UidProcessor;
use RdKafka\Message;
use RdKafka\TopicPartition;
use Throwable;

class Logger implements LoggerContract
{
    private readonly MonologLogger $logger;

    public function __construct()
    {
        $handler = new StreamHandler('php://stdout');

        $handler->setFormatter(new JsonFormatter);
        $handler->pushProcessor(new UidProcessor(32));

        $this->logger = new MonologLogger('PHP-KAFKA-CONSUMER-ERROR');
        $this->logger->pushHandler($handler);
    }

    /** Log an error consuming a message. */
    public function error(Message $message, ?Throwable $e = null, string $prefix = 'ERROR'): void
    {
        $this->logger->error("[{$prefix}] Error to consume message", [
            'message' => $message,
            'throwable' => $e,
        ]);
    }

    /** Log an error committing offsets. */
    public function commitError(ConsumerMessage|Message|array|null $messageOrOffsets, Throwable $e): void
    {
        $this->logger->error('[COMMIT_ERROR] Error to commit offsets', [
            'current_assignment' => $messageOrOffsets === null,
            'offsets' => $this->describeOffsets($messageOrOffsets),
            'throwable' => $e,
        ]);
    }

    /**
     * Describe the offsets the commit sent to Kafka. Messages are committed as the offset right after
     * them, and topic partitions are described through their getters, as they have no public
     * properties to be logged. Without offsets, the current assignment was being committed.
     *
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets
     * @return list<array{topic: ?string, partition: ?int, offset: ?int}>
     */
    private function describeOffsets(ConsumerMessage|Message|array|null $messageOrOffsets): array
    {
        return match (true) {
            $messageOrOffsets === null => [],
            $messageOrOffsets instanceof Message => [[
                'topic' => $messageOrOffsets->topic_name,
                'partition' => $messageOrOffsets->partition,
                'offset' => $messageOrOffsets->offset + 1,
            ]],
            $messageOrOffsets instanceof ConsumerMessage => [[
                'topic' => $messageOrOffsets->getTopicName(),
                'partition' => $messageOrOffsets->getPartition(),
                'offset' => $messageOrOffsets->getOffset() === null ? null : $messageOrOffsets->getOffset() + 1,
            ]],
            default => array_values(array_map(fn (TopicPartition $offset): array => [
                'topic' => $offset->getTopic(),
                'partition' => $offset->getPartition(),
                'offset' => $offset->getOffset(),
            ], array_filter($messageOrOffsets, fn (mixed $offset): bool => $offset instanceof TopicPartition))),
        };
    }
}
