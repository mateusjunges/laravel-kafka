<?php

namespace Junges\Kafka\Tests;

use Illuminate\Support\Str;
use Junges\Kafka\Message;

class MessageTest extends TestCase
{
    private Message $message;

    public function setUp(): void
    {
        parent::setUp();
        $this->message = new Message();
    }

    public function testItCanSetAMessageKey()
    {
        $this->message->withMessageKey('foo', 'bar');

        $expected  = new Message(message: ['foo' => 'bar']);

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanForgetAMessageKey()
    {
        $this->message->withMessageKey('foo', 'bar');
        $this->message->withMessageKey('bar', 'foo');

        $expected  = new Message(message: ['bar' => 'foo']);

        $this->message->forgetMessageKey('foo');

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanSetMessageHeaders()
    {
        $this->message->withHeaders([
            'foo' => 'bar'
        ]);

        $expected  = new Message(headers: ['foo' => 'bar']);

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanSetTheMessageKey()
    {
        $this->message->withKey($uuid = Str::uuid()->toString());

        $expected  = new Message(key: $uuid);

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanGetTheMessagePayload()
    {
        $this->message->withMessageKey('foo', 'bar');
        $this->message->withMessageKey('bar', 'foo');

        $expectedMessage  = new Message(message: $array = ['foo' => 'bar', 'bar' => 'foo']);

        $this->assertEquals($expectedMessage, $this->message);

        $expectedPayload = json_encode($array);

        $this->assertEquals($expectedPayload, $this->message->getPayload());
    }

    public function testItCanTransformAMessageInArray()
    {
        $this->message->withMessageKey('foo', 'bar');
        $this->message->withMessageKey('bar', 'foo');
        $this->message->withKey($uuid = Str::uuid()->toString());
        $this->message->withHeaders($headers = ['foo' => 'bar']);

        $expectedMessage  = new Message(
            headers: $headers,
            message: $array = ['foo' => 'bar', 'bar' => 'foo'],
            key: $uuid
        );

        $expectedArray = [
            'payload' => $array,
            'key' => $uuid,
            'headers' => $headers
        ];

        $this->assertEquals($expectedMessage, $this->message);
        $this->assertEquals($expectedArray, $this->message->toArray());
    }
}
