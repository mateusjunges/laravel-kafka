<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message;

use Illuminate\Support\Str;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

final class MessageTest extends LaravelKafkaTestCase
{
    private Message $message;

    protected function setUp(): void
    {
        parent::setUp();
        $this->message = new Message;
    }

    #[Test]
    public function it_can_set_a_message_key(): void
    {
        $this->message->withBodyKey('foo', 'bar');

        $expected = new Message(
            body: ['foo' => 'bar']
        );

        $this->assertEquals($expected, $this->message);
    }

    #[Test]
    public function it_can_forget_a_message_key(): void
    {
        $this->message->withBodyKey('foo', 'bar');
        $this->message->withBodyKey('bar', 'foo');

        $expected = new Message(
            body: ['bar' => 'foo']
        );

        $this->message->forgetBodyKey('foo');

        $this->assertEquals($expected, $this->message);
    }

    #[Test]
    public function it_can_set_message_headers(): void
    {
        $this->message->withHeaders([
            'foo' => 'bar',
        ]);

        $expected = new Message(
            headers: ['foo' => 'bar']
        );

        $this->assertEquals($expected, $this->message);
    }

    #[Test]
    public function it_can_set_the_message_key(): void
    {
        $this->message->withKey($uuid = Str::uuid()->toString());

        $expected = new Message(
            key: $uuid
        );

        $this->assertEquals($expected, $this->message);
    }

    #[Test]
    public function it_can_get_the_message_payload(): void
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

    #[Test]
    public function it_can_transform_a_message_in_array(): void
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
