<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Illuminate\Support\Str;
use Junges\Kafka\Consumers\CallableConsumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\Message;
use stdClass;

final class CallableConsumerTest extends LaravelKafkaTestCase
{
    public function testItDecodesMessages(): void
    {
        $message = new Message();
        $message->payload =
            <<<JSON
            {"foo": "bar"}
            JSON;
        $message->key = Str::uuid()->toString();
        $message->topic_name = 'test-topic';
        $message->partition = 1;
        $message->headers = [];
        $message->offset = 0;

        $consumer = new CallableConsumer($this->handleMessage(...), [
            function (ConsumerMessage $message, callable $next): void {
                $decoded = json_decode($message->getBody());
                $next($decoded);
            },
            function (stdClass $message, callable $next): void {
                $decoded = (array) $message;
                $next($decoded);
            },
        ]);

        $consumer->handle($this->getConsumerMessage($message));
    }

    public function handleMessage(array $data): void
    {
        $this->assertEquals([
            'foo' => 'bar',
        ], $data);
    }
}
