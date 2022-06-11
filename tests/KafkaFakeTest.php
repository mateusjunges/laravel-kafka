<?php

namespace Junges\Kafka\Tests;

use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Illuminate\Support\Collection;
use Junges\Kafka\Producers\MessageBatch;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Support\Testing\Fakes\KafkaFake;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use Junges\Kafka\Support\Testing\Fakes\ConsumerFake;

class KafkaFakeTest extends LaravelKafkaTestCase
{
    private KafkaFake $fake;
    private ConsumerFake $consumer;

    public function setUp(): void
    {
        parent::setUp();
        $this->fake = new KafkaFake();
    }

    public function testItReturnsAKafkaFakeInstance()
    {
        $kafka = Kafka::fake();

        $this->assertInstanceOf(KafkaFake::class, $kafka);
    }

    public function testItStorePublishedMessagesOnArray()
    {
        $producer = $this->fake->publishOn('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());
    }

    public function testAssertPublished()
    {
        try {
            $this->fake->assertPublished(new Message('foo'));
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('The expected message was not published.'));
        }

        $producer = $this->fake->publishOn('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());
        $producer->send();

        $this->fake->assertPublished($producer->getMessage());
    }

    public function testAssertPublishedTimes()
    {
        try {
            $this->fake->assertPublished(new Message('foo'));
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('The expected message was not published.'));
        }

        $producer = $this->fake->publishOn('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublishedTimes(1, $producer->getMessage());

        $producer->send();

        $this->fake->assertPublishedTimes(2, $producer->getMessage());

        try {
            $this->fake->assertPublishedTimes(2);
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('Kafka published 1 messages instead of 2.'));
        }
    }

    public function testItCanPerformAssertionsOnPublishedMessages()
    {
        $producer = $this->fake->publishOn('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey($uuid = Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());


        $this->fake->assertPublished($producer->getMessage(), function ($message) use ($uuid) {
            return $message->getKey() === $uuid;
        });

        $this->fake->assertPublished($message = $producer->getMessage(), function () use ($message, $uuid) {
            return $message->getKey() === $uuid;
        });

        try {
            $this->fake->assertPublished($message = $producer->getMessage(), function () use ($message, $uuid) {
                return $message->getKey() === 'not-published-uuid';
            });
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('The expected message was not published.'));
        }
    }

    public function testAssertPublishedOn()
    {
        $producer = $this->fake->publishOn('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());

        $this->fake->assertPublishedOn('topic', $producer->getMessage());

        try {
            $this->fake->assertPublishedOn('not-published-on-this-topic', $producer->getMessage());
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('The expected message was not published.'));
        }
    }

    public function testAssertPublishedOnTimes()
    {
        $producer = $this->fake->publishOn('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());

        $this->fake->assertPublishedOnTimes('topic', 1, $producer->getMessage());

        try {
            $this->fake->assertPublishedOnTimes('topic', 4, $producer->getMessage());
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('Kafka published 1 messages instead of 4.'));
        }
    }

    public function testICanPerformAssertionsUsingAssertPublishedOn()
    {
        $producer = $this->fake->publishOn('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey($uuid = Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());

        $this->fake->assertPublishedOn('topic', $producer->getMessage());

        try {
            $this->fake->assertPublishedOn('topic', $producer->getMessage(), function ($message) {
                return $message->getKey() === 'different-key';
            });
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('The expected message was not published.'));
        }

        $this->fake->assertPublishedOn('topic', $producer->getMessage(), function ($message) use ($uuid) {
            return $message->getKey() === $uuid;
        });
    }

    public function testNothingPublished()
    {
        $this->fake->assertNothingPublished();

        $this->fake->publishOn('topic', 'broker')->withMessage(new Message('foo'))->send();

        try {
            $this->fake->assertNothingPublished();
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('Messages were published unexpectedly.'));
        }
    }

    public function testPublishMessageBatch()
    {
        $messageBatch = new MessageBatch();
        $messageBatch->push(new Message());
        $messageBatch->push(new Message());
        $messageBatch->push(new Message());

        $producer = $this->fake->publishOn('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());

        $this->assertEquals(3, $producer->sendBatch($messageBatch));
    }

    public function testFakeConsumer()
    {
        Kafka::fake();
        $msg = new ConsumedMessage(
            topicName: 'test-topic',
            partition: 0,
            headers: [],
            body: ['test'],
            key: null,
            offset: 0,
            timestamp: 0
        );

        Kafka::shouldReceiveMessages(
            $msg
        );

        $consumer = Kafka::createConsumer()
            ->subscribe(['test-topic'])
            ->withBrokers('localhost:9092')
            ->withConsumerGroupId('group')
            ->withCommitBatchSize(1)
            ->withHandler(fn (KafkaConsumerMessage $message) => $this->assertEquals($msg, $message))
            ->build();
        $consumer->consume();
    }

    public function testFakeConsumerWithSingleMultipleMessages()
    {
        Kafka::fake();
        $msgs = [
            new ConsumedMessage(
                topicName: 'test-topic',
                partition: 0,
                headers: [],
                body: ['test'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
            new ConsumedMessage(
                topicName: 'test-topic-2',
                partition: 0,
                headers: [],
                body: ['test2'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
        ];

        Kafka::shouldReceiveMessages(
            $msgs
        );

        $consumedMessages = [];

        $consumer = Kafka::createConsumer(
            ['test-topic'],
        )->withHandler(function (KafkaConsumerMessage $message) use (&$consumedMessages) {
            $consumedMessages[] = $message;
        })
            ->build();

        $consumer->consume();

        $this->assertEquals($msgs, $consumedMessages);
        $this->assertEquals(count($msgs), $consumer->consumedMessagesCount());
    }

    public function testStopFakeConsumer()
    {
        Kafka::fake();
        $msgs = [
            new ConsumedMessage(
                topicName: 'test-topic',
                partition: 0,
                headers: [],
                body: ['test'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
            new ConsumedMessage(
                topicName: 'test-topic-2',
                partition: 0,
                headers: [],
                body: ['test2'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
        ];

        Kafka::shouldReceiveMessages(
            $msgs
        );

        $stopped = false;
        $this->consumer = Kafka::createConsumer(
            ['test-topic'],
        )->withHandler(function (KafkaConsumerMessage $message) use (&$stopped) {
            //stop consumer after first message
            $this->consumer->stopConsume(function () use (&$stopped) {
                $stopped = true;
            });
        })
            ->build();

        $this->consumer->consume();
        //testing stop callback
        $this->assertTrue($stopped);
        //should have consumed only one message
        $this->assertEquals(1, $this->consumer->consumedMessagesCount());
    }

    public function testFakeBatchConsumer()
    {
        Kafka::fake();
        $msgs = [
            new ConsumedMessage(
                topicName: 'test-topic',
                partition: 0,
                headers: [],
                body: ['test'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
            new ConsumedMessage(
                topicName: 'test-topic-2',
                partition: 0,
                headers: [],
                body: ['test2'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
        ];

        Kafka::shouldReceiveMessages(
            $msgs
        );

        $consumedMessages = [];
        $consumer = Kafka::createConsumer(
            ['test-topic'],
        )
            ->enableBatching()
            ->withBatchSizeLimit(10)
            ->withHandler(function (Collection $messages) use (&$consumedMessages) {
                $consumedMessages = $messages->toArray();
            })
            ->build();

        $consumer->consume();
        $this->assertEquals($msgs, $consumedMessages);
        $this->assertEquals(count($msgs), $consumer->consumedMessagesCount());
    }

    public function testFakeMultipleBatchConsumer()
    {
        Kafka::fake();
        $msgs = [
            new ConsumedMessage(
                topicName: 'test-topic',
                partition: 0,
                headers: [],
                body: ['test'],
                key: null,
                offset: 0,
                timestamp: 0
            ),

            new ConsumedMessage(
                topicName: 'test-topic-2',
                partition: 0,
                headers: [],
                body: ['test2'],
                key: null,
                offset: 0,
                timestamp: 0
            ),

            new ConsumedMessage(
                topicName: 'test-topic-3',
                partition: 0,
                headers: [],
                body: ['test3'],
                key: null,
                offset: 0,
                timestamp: 0
            ),

            new ConsumedMessage(
                topicName: 'test-topic-4',
                partition: 0,
                headers: [],
                body: ['test4'],
                key: null,
                offset: 0,
                timestamp: 0
            ),

            new ConsumedMessage(
                topicName: 'test-topic-5',
                partition: 0,
                headers: [],
                body: ['test5'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
        ];

        Kafka::shouldReceiveMessages(
            $msgs
        );

        $firstBatch = [];
        $secondBatch = [];
        $thirdBatch = [];

        $consumer = Kafka::createConsumer(
            ['test-topic'],
        )
            ->enableBatching()
            ->withBatchSizeLimit(2)
            ->withHandler(function (Collection $messages) use (&$firstBatch, &$secondBatch, &$thirdBatch) {
                if (count($firstBatch) == 0) {
                    $firstBatch = $messages->toArray();
                    $this->assertEquals(2, $messages->count());
                } else if (count($secondBatch) == 0) {
                    $secondBatch = $messages->toArray();
                    $this->assertEquals(2, $messages->count());
                } else {
                    $thirdBatch = $messages->toArray();
                    $this->assertEquals(1, $messages->count());
                }
            })
            ->build();

        $consumer->consume();

        $this->assertEquals($msgs, array_merge($firstBatch, $secondBatch, $thirdBatch));
        $this->assertEquals(count($msgs), $consumer->consumedMessagesCount());
    }

    public function testStopFakeBatchConsumer()
    {
        Kafka::fake();
        $msgs = [
            new ConsumedMessage(
                topicName: 'test-topic',
                partition: 0,
                headers: [],
                body: ['test'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
            new ConsumedMessage(
                topicName: 'test-topic-2',
                partition: 0,
                headers: [],
                body: ['test2'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
            new ConsumedMessage(
                topicName: 'test-topic-3',
                partition: 0,
                headers: [],
                body: ['test3'],
                key: null,
                offset: 0,
                timestamp: 0
            ),
        ];

        Kafka::shouldReceiveMessages(
            $msgs
        );

        $stopped = false;
        $this->consumer = Kafka::createConsumer(['test-topic'])
            ->enableBatching()
            ->withBatchSizeLimit(2)
            ->withHandler(function (Collection $messages) use (&$stopped) {
                //stop consumer after first batch
                $this->consumer->stopConsume(function () use (&$stopped) {
                    $stopped = true;
                });
            })
            ->build();

        $this->consumer->consume();
        //testing stop callback
        $this->assertTrue($stopped);
        //should have consumed only two messages
        $this->assertEquals(2, $this->consumer->consumedMessagesCount());
    }
}
