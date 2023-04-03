<?php

namespace Junges\Kafka\Tests\Message;

use Illuminate\Support\Str;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class MessageTest extends LaravelKafkaTestCase
{
    /**
     * @var \Junges\Kafka\Message\Message
     */
    private $message;

    public function setUp(): void
    {
        parent::setUp();
        $this->message = new Message();
    }

    public function testItCanSetAMessageKey()
    {
        $this->message->withBodyKey('foo', 'bar');

        $expected = new Message(
            null, -1, [], ['foo' => 'bar']
        );

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanForgetAMessageKey()
    {
        $this->message->withBodyKey('foo', 'bar');
        $this->message->withBodyKey('bar', 'foo');

        $expected = new Message(
            null, -1, [], ['bar' => 'foo']
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
            null, -1, ['foo' => 'bar']
        );

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanSetTheMessageKey()
    {
        $this->message->withKey($uuid = Str::uuid()->toString());

        $expected = new Message(
            null, -1, [], [], $uuid
        );

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanGetTheMessagePayload()
    {
        $this->message->withBodyKey('foo', 'bar');
        $this->message->withBodyKey('bar', 'foo');

        $expectedMessage = new Message(
            null, -1, [], $array = ['foo' => 'bar', 'bar' => 'foo']
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
            null,
            -1,
            $headers,
            $array = ['foo' => 'bar', 'bar' => 'foo'],
            $uuid
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
