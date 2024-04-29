<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Manager;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;
use Junges\Kafka\Support\Testing\Fakes\KafkaFake;
use PHPUnit\Framework\Constraint\ExceptionMessageIsOrContains;
use PHPUnit\Framework\ExpectationFailedException;

final class KafkaFakeTest extends LaravelKafkaTestCase
{
    private KafkaFake $fake;
    private MessageConsumer $consumer;

    public function setUp(): void
    {
        parent::setUp();
        $this->fake = new KafkaFake(app(Manager::class));
    }

    public function testItStorePublishedMessagesOnArray(): void
    {
        $producer = $this->fake->publish()
            ->onTopic('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());
    }

    public function testAssertPublished(): void
    {
        try {
            $this->fake->assertPublished(new Message('foo'));
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessageIsOrContains('The expected message was not published.'));
        }

        $producer = $this->fake->publish()
            ->onTopic('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());
        $producer->send();

        $this->fake->assertPublished($producer->getMessage());
    }

    public function testAssertPublishedTimes(): void
    {
        try {
            $this->fake->assertPublished(new Message('foo'));
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessageIsOrContains('The expected message was not published.'));
        }

        $producer = $this->fake->publish()
            ->onTopic('topic')
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
            $this->assertThat($exception, new ExceptionMessageIsOrContains('Kafka published 1 messages instead of 2.'));
        }
    }

    public function testItCanPerformAssertionsOnPublishedMessages(): void
    {
        $producer = $this->fake->publish()
            ->onTopic('topic')
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
            $this->assertThat($exception, new ExceptionMessageIsOrContains('The expected message was not published.'));
        }
    }

    public function testAssertPublishedOn(): void
    {
        $producer = $this->fake->publish()
            ->onTopic('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());

        $this->fake->assertPublishedOn('topic', $producer->getMessage());

        try {
            $this->fake->assertPublishedOn('not-published-on-this-topic', $producer->getMessage());
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessageIsOrContains('The expected message was not published.'));
        }
    }

    public function testAssertPublishedOnBySpecifyingMessageObject(): void
    {
        $message = Message::create()->withBody(['test' => ['test']])->withHeaders(['custom' => 'header'])->withKey(Str::uuid()->toString());

        $producer = $this->fake->publish()->onTopic('topic')->withMessage($message);

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());

        $this->fake->assertPublishedOn('topic', $producer->getMessage());

        try {
            $this->fake->assertPublishedOn('not-published-on-this-topic', $producer->getMessage());
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessageIsOrContains('The expected message was not published.'));
        }
    }

    public function testAssertPublishedOnTimes(): void
    {
        $producer = $this->fake->publish()
            ->onTopic('topic')
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());

        $this->fake->assertPublishedOnTimes('topic', 1, $producer->getMessage());

        try {
            $this->fake->assertPublishedOnTimes('topic', 4, $producer->getMessage());
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessageIsOrContains('Kafka published 1 messages instead of 4.'));
        }
    }

    public function testAssertPublishedOnTimesForBatchMessages(): void
    {
        $producer = $this->fake->publish()
            ->onTopic('batch-topic')
            ->withConfigOption('key', 'value');

        $message = new Message(
            headers: ['header-key' => 'header-value'],
            body: ['body-key' => 'body-value'],
            key: 2
        );

        $messageBatch = (new MessageBatch())->onTopic('batch-topic');
        $messageBatch->push($message);
        $messageBatch->push($message);

        $producer->sendBatch($messageBatch);

        $this->fake->assertPublishedTimes(2);
        $this->fake->assertpublished();
        $this->fake->assertPublishedOnTimes('batch-topic', 2);
        $this->fake->assertPublishedOn('batch-topic');
    }

    public function testICanPerformAssertionsUsingAssertPublishedOn(): void
    {
        $producer = $this->fake->publish()
            ->onTopic('topic')
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
            $this->assertThat($exception, new ExceptionMessageIsOrContains('The expected message was not published.'));
        }

        $this->fake->assertPublishedOn('topic', $producer->getMessage(), function ($message) use ($uuid) {
            return $message->getKey() === $uuid;
        });
    }

    public function testNothingPublished(): void
    {
        $this->fake->assertNothingPublished();

        $this->fake->publish('broker')->withMessage(new Message('foo'))->send();

        try {
            $this->fake->assertNothingPublished();
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessageIsOrContains('Messages were published unexpectedly.'));
        }
    }

    public function testPublishMessageBatch(): void
    {
        $messageBatch = (new MessageBatch())->onTopic('test');
        $messageBatch->push(new Message());
        $messageBatch->push(new Message());
        $messageBatch->push(new Message());

        $producer = $this->fake->publish()
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKafkaKey(Str::uuid()->toString());

        $this->assertEquals(3, $producer->sendBatch($messageBatch));
    }

    public function testFakeConsumer(): void
    {
        Kafka::fake();

        $message = new ConsumedMessage(
            topicName: 'test-topic',
            partition: 0,
            headers: [],
            body: ['test'],
            key: null,
            offset: 0,
            timestamp: 0
        );

        Kafka::shouldReceiveMessages($message);

        $consumer = Kafka::consumer()
            ->subscribe(['test-topic'])
            ->withBrokers('localhost:9092')
            ->withConsumerGroupId('group')
            ->withCommitBatchSize(1)
            ->withHandler(fn (ConsumerMessage $message) => $this->assertEquals($message, $message))
            ->build();

        $consumer->consume();
    }

    public function testFakeConsumerWithSingleMultipleMessages(): void
    {
        Kafka::fake();

        $messages = [
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

        Kafka::shouldReceiveMessages($messages);

        $consumedMessages = [];

        $consumer = Kafka::consumer(['test-topic'])
            ->withHandler(function (ConsumerMessage $message) use (&$consumedMessages) {
                $consumedMessages[] = $message;
            })
            ->build();

        $consumer->consume();

        $this->assertEquals($messages, $consumedMessages);
        $this->assertEquals(count($messages), $consumer->consumedMessagesCount());
    }

    public function testAReceivedMessageDoesItsJob(): void
    {
        Kafka::fake();

        $now = Carbon::create(1998, 8, 11, 4, 30);

        $posts = [
             1 => [
                'id' => 1,
                 'published_at' => null,
                 'title' => 'Hey Jude',
                 'content' => "Don't make it bad, take a sad song and make it better",
            ],
        ];

        Carbon::setTestNow($now);

        $messages = [
            new ConsumedMessage(
                topicName: 'mark-post-as-published-topic',
                partition: 0,
                headers: [],
                body: ['post_id' => 1],
                key: null,
                offset: 0,
                timestamp: 0
            ),
        ];

        Kafka::shouldReceiveMessages($messages);

        $consumer = Kafka::consumer(['mark-post-as-published-topic'])
            ->withHandler(function (ConsumerMessage $message) use (&$posts) {
                $post = $posts[$message->getBody()['post_id']];

                $post['published_at'] = now()->format("Y-m-d H:i:s");

                $posts[1] = $post;

                return 0;
            })->build();

        $consumer->consume();

        $this->assertEquals('1998-08-11 04:30:00', $posts[1]['published_at']);
    }

    public function testStopFakeConsumer(): void
    {
        Kafka::fake();

        $messages = [
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

        Kafka::shouldReceiveMessages($messages);

        $stopped = false;
        $this->consumer = Kafka::consumer(['test-topic'])
            ->withHandler(function (ConsumerMessage $message) use (&$stopped) {
                //stop consumer after first message
                $this->consumer->stopConsuming();
            })
            ->onStopConsuming(function () use (&$stopped) {
                $stopped = true;
            })
            ->build();

        $this->consumer->consume();

        //testing stop callback
        $this->assertTrue($stopped);
        //should have consumed only one message
        $this->assertEquals(1, $this->consumer->consumedMessagesCount());
    }

    public function testFakeBatchConsumer(): void
    {
        Kafka::fake();

        $messages = [
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

        Kafka::shouldReceiveMessages($messages);

        $consumedMessages = [];
        $consumer = Kafka::consumer(['test-topic'])
            ->enableBatching()
            ->withBatchSizeLimit(10)
            ->withHandler(function (Collection $messages) use (&$consumedMessages) {
                $consumedMessages = $messages->toArray();
            })
            ->build();

        $consumer->consume();
        $this->assertEquals($messages, $consumedMessages);
        $this->assertEquals(count($messages), $consumer->consumedMessagesCount());
    }

    public function testFakeMultipleBatchConsumer(): void
    {
        Kafka::fake();

        $messages = [
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

        Kafka::shouldReceiveMessages($messages);

        $firstBatch = [];
        $secondBatch = [];
        $thirdBatch = [];

        $consumer = Kafka::consumer(['test-topic'])
            ->enableBatching()
            ->withBatchSizeLimit(2)
            ->withHandler(function (Collection $messages) use (&$firstBatch, &$secondBatch, &$thirdBatch) {
                if (count($firstBatch) == 0) {
                    $firstBatch = $messages->toArray();
                    $this->assertEquals(2, $messages->count());
                } elseif (count($secondBatch) == 0) {
                    $secondBatch = $messages->toArray();
                    $this->assertEquals(2, $messages->count());
                } else {
                    $thirdBatch = $messages->toArray();
                    $this->assertEquals(1, $messages->count());
                }
            })
            ->build();

        $consumer->consume();

        $this->assertEquals($messages, array_merge($firstBatch, $secondBatch, $thirdBatch));
        $this->assertEquals(count($messages), $consumer->consumedMessagesCount());
    }

    public function testStopFakeBatchConsumer(): void
    {
        Kafka::fake();

        $messages = [
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

        Kafka::shouldReceiveMessages($messages);

        $this->consumer = Kafka::consumer(['test-topic'])
            ->enableBatching()
            ->withBatchSizeLimit(2)
            ->withHandler(function (Collection $messages, MessageConsumer $consumer) {
                //stop consumer after first batch
                $consumer->stopConsuming();
            })
            ->build();

        $this->consumer->consume();

        //should have consumed only two messages
        $this->assertEquals(2, $this->consumer->consumedMessagesCount());
    }

    /** @test */
    public function it_can_handle_macros(): void
    {
        Kafka::macro('onTopicExample', fn () => 'this is a test');

        $this->assertSame('this is a test', $this->fake->onTopicExample());
    }
}
