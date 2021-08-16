<?php

namespace Junges\Kafka\Tests;

use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message;
use Mockery as m;
use RdKafka\Producer;

class KafkaTest extends TestCase
{
    public function testItCanPublishMessagesToKafka()
    {
        $message = (new Message())->setMessageKey('test', [
            'test-nested-key' => 'test'
        ]);

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic', 'produce')
            ->andReturnSelf()
            ->andReturn()
            ->getMock();

        $this->app->instance(Producer::class, $mockedProducer);

        $test = Kafka::publish($message, 'test-topic');

        $this->assertTrue($test);
    }
}