<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message;

use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ConsumedMessageTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_is_on_its_first_attempt_by_default(): void
    {
        $this->assertSame(1, $this->makeMessage()->getAttempts());
    }

    #[Test]
    public function it_returns_a_copy_with_the_given_attempts(): void
    {
        $message = $this->makeMessage();

        $retried = $message->withAttempts(3);

        $this->assertSame(3, $retried->getAttempts());
        $this->assertSame(1, $message->getAttempts());
        $this->assertSame($message->getBody(), $retried->getBody());
        $this->assertSame($message->getOffset(), $retried->getOffset());
    }

    private function makeMessage(): ConsumedMessage
    {
        return new ConsumedMessage(
            topicName: 'topic',
            partition: 0,
            headers: [],
            body: ['foo' => 'bar'],
            key: 'key',
            offset: 10,
            timestamp: null,
        );
    }
}
