<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Junges\Kafka\Logger;
use Junges\Kafka\Message\ConsumedMessage;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RdKafka\Exception;
use RdKafka\Message;
use RdKafka\TopicPartition;
use ReflectionProperty;

final class LoggerTest extends TestCase
{
    #[Test]
    public function it_logs_the_topic_partitions_that_failed_to_commit(): void
    {
        [$logger, $handler] = $this->logger();

        $logger->commitError([new TopicPartition('orders', 1, 10), new TopicPartition('orders', 2, 20)], new Exception('Commit failed'));

        $this->assertSame([
            'current_assignment' => false,
            'offsets' => [
                ['topic' => 'orders', 'partition' => 1, 'offset' => 10],
                ['topic' => 'orders', 'partition' => 2, 'offset' => 20],
            ],
        ], $this->loggedContext($handler));
    }

    #[Test]
    public function it_logs_the_offset_after_the_messages_that_failed_to_commit(): void
    {
        [$logger, $handler] = $this->logger();

        $message = new Message;
        $message->topic_name = 'orders';
        $message->partition = 1;
        $message->offset = 10;

        $logger->commitError($message, new Exception('Commit failed'));
        $logger->commitError(new ConsumedMessage(topicName: 'orders', partition: 2, offset: 20), new Exception('Commit failed'));

        $this->assertSame([['topic' => 'orders', 'partition' => 1, 'offset' => 11]], $this->loggedContext($handler, 0)['offsets']);
        $this->assertSame([['topic' => 'orders', 'partition' => 2, 'offset' => 21]], $this->loggedContext($handler, 1)['offsets']);
    }

    #[Test]
    public function it_logs_when_the_current_assignment_failed_to_commit(): void
    {
        [$logger, $handler] = $this->logger();

        $logger->commitError(null, new Exception('Commit failed'));

        $this->assertSame(['current_assignment' => true, 'offsets' => []], $this->loggedContext($handler));
        $this->assertStringContainsString('[COMMIT_ERROR]', $handler->getRecords()[0]->message);
    }

    /** @return array{Logger, TestHandler} */
    private function logger(): array
    {
        $logger = new Logger;

        $handler = new TestHandler;
        $handler->setFormatter(new JsonFormatter);

        (new ReflectionProperty(Logger::class, 'logger'))->getValue($logger)->setHandlers([$handler]);

        return [$logger, $handler];
    }

    /** The context of a log record, as written by the JSON formatter, without the exception. */
    private function loggedContext(TestHandler $handler, int $record = 0): array
    {
        $context = json_decode($handler->getRecords()[$record]->formatted, true)['context'];

        $this->assertSame(Exception::class, $context['throwable']['class']);

        unset($context['throwable']);

        return $context;
    }
}
