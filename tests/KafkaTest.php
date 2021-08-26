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
            ->shouldReceive('newTopic', 'produce')
            ->andReturnSelf()
            ->andReturn()
            ->getMock();

        $this->app->instance(Producer::class, $mockedProducer);

        $test = Kafka::publishOn('localhost:9092', 'test-topic')
            ->withKey(Str::uuid()->toString())
            ->withMessageKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled()
            ->send();

        $this->assertTrue($test);
    }
}