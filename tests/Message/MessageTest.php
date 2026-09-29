<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message;

use Illuminate\Support\Str;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

final class MessageTest extends LaravelKafkaTestCase
{
    private Message $message;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->message = new Message;
    }

    #[Test]
    public function it_can_set_the_message_body(): void
    {
        $this->message->withBody(['foo' => 'bar']);

        $expected = $this->expectedMessage(body: ['foo' => 'bar']);

        $this->assertEquals($expected, $this->message);
    }

    #[Test]
    public function it_can_set_message_headers(): void
    {
        $this->message->withHeaders([
            'foo' => 'bar',
        ]);

        $expected = $this->expectedMessage(headers: ['foo' => 'bar']);

        $this->assertEquals($expected, $this->message);
    }

    #[Test]
    public function it_can_set_the_message_key(): void
    {
        $this->message->withKey($uuid = Str::uuid()->toString());

        $expected = $this->expectedMessage(key: $uuid);

        $this->assertEquals($expected, $this->message);
    }

    #[Test]
    public function it_can_get_the_message_payload(): void
    {
        $this->message->withBody(['foo' => 'bar', 'bar' => 'foo']);

        $expectedMessage = $this->expectedMessage(body: $array = ['foo' => 'bar', 'bar' => 'foo']);

        $this->assertEquals($expectedMessage, $this->message);

        $expectedPayload = $array;

        $this->assertEquals($expectedPayload, $this->message->getBody());
    }

    #[Test]
    public function it_can_transform_a_message_in_array(): void
    {
        $this->message->withBody(['foo' => 'bar', 'bar' => 'foo']);
        $this->message->withKey($uuid = Str::uuid()->toString());
        $this->message->withHeaders($headers = ['foo' => 'bar']);

        $expectedMessage = $this->expectedMessage(
            headers: $headers,
            body: $array = ['foo' => 'bar', 'bar' => 'foo'],
            key: $uuid
        );

        $expectedArray = [
            'payload' => $array,
            'key' => $uuid,
            'headers' => [...$headers, 'laravel-kafka::message-id' => $this->message->getMessageIdentifier()],
        ];

        $this->assertEquals($expectedMessage, $this->message);
        $this->assertEquals($expectedArray, $this->message->toArray());
    }

    #[Test]
    public function it_keeps_the_same_id_for_the_life_of_the_message(): void
    {
        $id = $this->message->getMessageIdentifier();

        $this->message->withBody(['foo' => 'bar'])->withHeader('foo', 'bar')->withHeaders(['bar' => 'baz']);

        $this->assertSame($id, $this->message->getMessageIdentifier());
        $this->assertSame($id, $this->message->getHeaders()['laravel-kafka::message-id']);
        $this->assertSame($id, (clone $this->message)->getMessageIdentifier());
    }

    #[Test]
    public function it_keeps_the_id_given_by_the_user(): void
    {
        $message = new Message(headers: ['laravel-kafka::message-id' => 'my-id']);

        $this->assertSame('my-id', $message->getMessageIdentifier());

        $message->withHeaders(['laravel-kafka::message-id' => 'other-id']);

        $this->assertSame('other-id', $message->getMessageIdentifier());
    }

    #[Test]
    public function every_message_gets_its_own_id(): void
    {
        $this->assertNotSame((new Message)->getMessageIdentifier(), (new Message)->getMessageIdentifier());
    }

    private function expectedMessage(array $headers = [], mixed $body = [], mixed $key = null): Message
    {
        return new Message(
            headers: ['laravel-kafka::message-id' => $this->message->getMessageIdentifier(), ...$headers],
            body: $body,
            key: $key,
        );
    }
}
