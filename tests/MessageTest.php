<?php

namespace Junges\Kafka\Tests;

use Illuminate\Support\Str;
use Junges\Kafka\Message\Message;

class MessageTest extends LaravelKafkaTestCase
{
    private Message $message;

    public function setUp(): void
    {
        parent::setUp();
        $this->message = new Message('foo');
    }

    public function testItCanSetAMessageKey()
    {
        $this->message->withBodyKey('foo', 'bar');

        $expected = new Message(
            topicName: 'foo',
            body: ['foo' => 'bar']
        );

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanForgetAMessageKey()
    {
        $this->message->withBodyKey('foo', 'bar');
        $this->message->withBodyKey('bar', 'foo');

        $expected = new Message(
            topicName: 'foo',
            body: ['bar' => 'foo']
        );

        $this->message->forgetBodyKey('foo');

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanSetMessageHeaders()
    {
        $this->message->withHeaders([
            'foo' => 'bar',
        ]);

        $expected = new Message(
            topicName: 'foo',
            headers: ['foo' => 'bar']
        );

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanSetTheMessageKey()
    {
        $this->message->withKey($uuid = Str::uuid()->toString());

        $expected = new Message(
            topicName: 'foo',
            key: $uuid
        );

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanGetTheMessagePayload()
    {
        $this->message->withBodyKey('foo', 'bar');
        $this->message->withBodyKey('bar', 'foo');

        $expectedMessage = new Message(
            topicName: 'foo',
            body: $array = ['foo' => 'bar', 'bar' => 'foo']
        );

        $this->assertEquals($expectedMessage, $this->message);

        $expectedPayload = $array;

        $this->assertEquals($expectedPayload, $this->message->getBody());
    }

    public function testItCanTransformAMessageInArray()
    {
        $this->message->withBodyKey('foo', 'bar');
        $this->message->withBodyKey('bar', 'foo');
        $this->message->withKey($uuid = Str::uuid()->toString());
        $this->message->withHeaders($headers = ['foo' => 'bar']);

        $expectedMessage = new Message(
            topicName: 'foo',
            headers: $headers,
            body: $array = ['foo' => 'bar', 'bar' => 'foo'],
            key: $uuid
        );

        $expectedArray = [
            'payload' => $array,
            'key' => $uuid,
            'headers' => $headers,
        ];

        $this->assertEquals($expectedMessage, $this->message);
        $this->assertEquals($expectedArray, $this->message->toArray());
    }
}
