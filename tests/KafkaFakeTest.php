<?php

namespace Junges\Kafka\Tests;

use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\KafkaFake;
use Junges\Kafka\Message;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;

class KafkaFakeTest extends TestCase
{
    private KafkaFake $fake;

    protected function setUp(): void
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
        $producer = $this->fake->publishOn('broker', 'test_topic_1')
            ->withMessageKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKey($uuid = Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());
    }

    public function testAssertPublished()
    {
        try {
            $this->fake->assertPublished(new Message());
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('The expected message was not published.'));
        }

        $producer = $this->fake->publishOn('broker', 'topic')
            ->withMessageKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKey($uuid = Str::uuid()->toString());
        $producer->send();

        $this->fake->assertPublished($producer->getMessage());
    }

    public function testItCanPerformAssertionsOnPublishedMessages()
    {
        $producer = $this->fake->publishOn('broker', 'topic')
            ->withMessageKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKey($uuid = Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());

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
        $producer = $this->fake->publishOn('broker', 'topic')
            ->withMessageKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withKey($uuid = Str::uuid()->toString());

        $producer->send();

        $this->fake->assertPublished($producer->getMessage());

        $this->fake->assertPublishedOn('topic', $producer->getMessage());

        try {
            $this->fake->assertPublishedOn('not-published-on-this-topic', $producer->getMessage());
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('The expected message was not published.'));
        }
    }

    public function testNothingPublished()
    {
        $this->fake->assertNothingPublished();

        $this->fake->publishOn('broker', 'topic')->withMessage(new Message())->send();

        try {
            $this->fake->assertNothingPublished();
        } catch (ExpectationFailedException $exception) {
            $this->assertThat($exception, new ExceptionMessage('Messages were published unexpectedly.'));
        }
    }
}
