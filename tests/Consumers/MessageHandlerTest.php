<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Illuminate\Support\Str;
use Junges\Kafka\Consumers\MessageHandler;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Tests\Fakes\FakeMiddleware;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Message;
use RuntimeException;
use stdClass;
use Throwable;

final class MessageHandlerTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_decodes_messages(): void
    {
        $message = new Message;
        $message->payload =
            <<<'JSON'
            {"foo": "bar"}
            JSON;
        $message->key = Str::uuid()->toString();
        $message->topic_name = 'test-topic';
        $message->partition = 1;
        $message->headers = [];
        $message->offset = 0;

        $messageConsumerMock = m::mock(Consumer::class);

        $handler = new MessageHandler($this->handleMessage(...), [
            function (ConsumerMessage $message, callable $next): void {
                $decoded = json_decode($message->getBody());
                $next($decoded);
            },
            function (stdClass $message, callable $next): void {
                $decoded = (array) $message;
                $next($decoded);
            },
        ]);

        $handler->handle($this->getConsumerMessage($message), $messageConsumerMock);
    }

    public function handleMessage(array $data): void
    {
        $this->assertEquals([
            'foo' => 'bar',
        ], $data);
    }

    #[Test]
    public function it_notifies_failures_to_the_failure_callback(): void
    {
        $received = null;

        $handler = new MessageHandler(fn () => null, [], function (ConsumerMessage $message, Throwable $exception) use (&$received) {
            $received = [$message, $exception];
        });

        $message = new ConsumedMessage('topic', 0, [], null, null, 0, null);
        $exception = new RuntimeException('fail');

        $handler->failed($message, $exception);

        $this->assertSame([$message, $exception], $received);
    }

    #[Test]
    public function it_resolves_middleware_classes_from_the_container(): void
    {
        FakeMiddleware::$messages = [];

        $handled = null;
        $handler = new MessageHandler(function (ConsumerMessage $message) use (&$handled) {
            $handled = $message;
        }, [FakeMiddleware::class]);

        $message = new ConsumedMessage('topic', 0, [], null, null, 0, null);

        $handler->handle($message, m::mock(Consumer::class));

        $this->assertSame([$message], FakeMiddleware::$messages);
        $this->assertSame($message, $handled);
    }
}
