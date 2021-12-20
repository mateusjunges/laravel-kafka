<?php

namespace Junges\Kafka\Tests\Consumers;

use Illuminate\Support\Str;
use Junges\Kafka\Consumers\CallableHandler;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\Message;
use stdClass;

class CallableHandlerTest extends LaravelKafkaTestCase
{
    public function testItDecodesMessages()
    {
        $message = new Message();
        $message->payload =
            <<<JSON
            {"foo": "bar"}
            JSON;
        $message->key = Str::uuid()->toString();
        $message->topic_name = 'test-topic';

        $consumer = new CallableHandler([$this, 'handleMessage'], [
            function (KafkaConsumerMessage $message, callable $next): void {
                $decoded = json_decode($message->getBody());
                $next($decoded);
            },
            function (stdClass $message, callable $next): void {
                $decoded = (array) $message;
                $next($decoded);
            },
        ]);

        $consumer->handle($this->getConsumerMessage($message));

        $consumer = new CallableHandler(function (KafkaConsumerMessage $message) {
            $this->assertEquals("{\"foo\": \"bar\"}", $message->getBody());
        });

        $consumer->handle($this->getConsumerMessage($message));
    }

    public function handleMessage(array $data): void
    {
        $this->assertEquals([
            'foo' => 'bar',
        ], $data);
    }
}
