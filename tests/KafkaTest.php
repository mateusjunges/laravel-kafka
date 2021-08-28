<?php

namespace Junges\Kafka\Tests;

use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Mockery as m;
use RdKafka\Producer;

class KafkaTest extends TestCase
{
    public function testItCanPublishMessagesToKafka()
    {
        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn(m::self())
            ->shouldReceive('producev')
            ->andReturn(m::self())
            ->shouldReceive('poll')
            ->andReturn(m::self())
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(Producer::class, function () use ($mockedProducer) {
            return $mockedProducer;
        });

        $test = Kafka::publishOn('localhost:9092', 'test-topic')
            ->withKafkaMessageKey(Str::uuid()->toString())
            ->withMessageKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled()
            ->send();

        $this->assertTrue($test);
    }
}
