<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message;

use Illuminate\Support\Str;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

final class MessageTest extends LaravelKafkaTestCase
{
    private Message $message;

    public function setUp(): void
    {
        parent::setUp();
        $this->message = new Message();
    }

    public function testItCanSetAMessageKey(): void
    {
        $this->message->withBodyKey('foo', 'bar');

        $expected = new Message(
            body: ['foo' => 'bar']
        );

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanForgetAMessageKey(): void
    {
        $this->message->withBodyKey('foo', 'bar');
        $this->message->withBodyKey('bar', 'foo');

        $expected = new Message(
            body: ['bar' => 'foo']
        );

        $this->message->forgetBodyKey('foo');

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanSetMessageHeaders(): void
    {
        $this->message->withHeaders([
            'foo' => 'bar',
        ]);

        $expected = new Message(
            headers: ['foo' => 'bar']
        );

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanSetTheMessageKey(): void
    {
        $this->message->withKey($uuid = Str::uuid()->toString());

        $expected = new Message(
            key: $uuid
        );

        $this->assertEquals($expected, $this->message);
    }

    public function testItCanGetTheMessagePayload(): void
    {
        $this->message->withBodyKey('foo', 'bar');
        $this->message->withBodyKey('bar', 'foo');

        $expectedMessage = new Message(
            body: $array = ['foo' => 'bar', 'bar' => 'foo']
        );

        $this->assertEquals($expectedMessage, $this->message);

        $expectedPayload = $array;

        $this->assertEquals($expectedPayload, $this->message->getBody());
    }

    public function testItCanTransformAMessageInArray(): void
    {
        $this->message->withBodyKey('foo', 'bar');
        $this->message->withBodyKey('bar', 'foo');
        $this->message->withKey($uuid = Str::uuid()->toString());
        $this->message->withHeaders($headers = ['foo' => 'bar']);

        $expectedMessage = new Message(
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
