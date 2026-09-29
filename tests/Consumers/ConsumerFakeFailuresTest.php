<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Events\MessageConsumed;
use Junges\Kafka\Events\MessageSentToDLQ;
use Junges\Kafka\Events\MessageSkipped;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Throwable;

final class ConsumerFakeFailuresTest extends LaravelKafkaTestCase
{
    /** @var list<string> */
    private array $handled = [];

    #[Test]
    public function it_retries_failed_messages_with_their_attempt_number(): void
    {
        Event::fake([MessageConsumed::class]);
        Sleep::fake();
        $this->receive('failing');

        $attempts = [];

        Kafka::consumer(['orders'])
            ->retryFailedMessages(2, backoffInMs: 100)
            ->withHandler(function (ConsumerMessage $message) use (&$attempts) {
                $attempts[] = $message->getAttempts();

                if ($message->getAttempts() < 3) {
                    throw new RuntimeException('fail');
                }
            })
            ->build()
            ->consume();

        $this->assertSame([1, 2, 3], $attempts);
        Sleep::assertSleptTimes(2);
        Event::assertDispatched(MessageConsumed::class, fn (MessageConsumed $event) => $event->message->getAttempts() === 3);
    }

    #[Test]
    public function it_waits_for_each_backoff_in_order_and_repeats_the_last_one(): void
    {
        Sleep::fake();
        $this->receive('failing');

        $this->consumer()
            ->retryFailedMessages(4, backoffInMs: [100, 1000, 5000])
            ->skipFailedMessages()
            ->build()
            ->consume();

        Sleep::assertSequence([
            Sleep::for(100)->milliseconds(),
            Sleep::for(1000)->milliseconds(),
            Sleep::for(5000)->milliseconds(),
            Sleep::for(5000)->milliseconds(),
        ]);
    }

    #[Test]
    public function it_does_not_accept_negative_backoffs(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Kafka::consumer(['orders'])->retryFailedMessages(3, backoffInMs: [100, -1]);
    }

    #[Test]
    public function it_stops_consuming_when_a_message_fails_by_default(): void
    {
        $this->receive('ok', 'failing', 'never-handled');

        try {
            $this->consumer()->build()->consume();

            $this->fail('The consumer should stop when a message fails.');
        } catch (ConsumerException $exception) {
            $this->assertStringContainsString('offset [1] of topic [orders]', $exception->getMessage());
        }

        $this->assertSame(['ok', 'failing'], $this->handled);
    }

    #[Test]
    public function it_skips_failed_messages_when_enabled(): void
    {
        Event::fake([MessageSkipped::class]);
        $this->receive('failing', 'ok');

        $this->consumer()->skipFailedMessages()->build()->consume();

        $this->assertSame(['failing', 'ok'], $this->handled);
        Event::assertDispatched(MessageSkipped::class, fn (MessageSkipped $event) => $event->message->getBody() === ['name' => 'failing']);
    }

    #[Test]
    public function it_sends_failed_messages_to_the_dead_letter_queue(): void
    {
        Event::fake([MessageSentToDLQ::class]);
        $this->receive('failing', 'ok');

        $this->consumer()->withDlq()->build()->consume();

        $this->assertSame(['failing', 'ok'], $this->handled);
        Event::assertDispatched(MessageSentToDLQ::class, fn (MessageSentToDLQ $event) => $event->payload === '{"name":"failing"}'
            && $event->throwable->getMessage() === 'fail');
    }

    #[Test]
    public function it_notifies_the_failure_callback(): void
    {
        $this->receive('failing');

        $failures = [];

        $this->consumer()
            ->skipFailedMessages()
            ->onMessageFailed(function (ConsumerMessage $message, Throwable $exception) use (&$failures) {
                $failures[] = $message->getBody()['name'].': '.$exception->getMessage();
            })
            ->build()
            ->consume();

        $this->assertSame(['failing: fail'], $failures);
    }

    #[Test]
    public function it_runs_the_before_and_after_consuming_callbacks(): void
    {
        $this->receive('ok', 'ok');

        $calls = [];

        $this->consumer()
            ->beforeConsuming(function () use (&$calls) {
                $calls[] = 'before';
            })
            ->afterConsuming(function () use (&$calls) {
                $calls[] = 'after';
            })
            ->build()
            ->consume();

        $this->assertSame(['before', 'after', 'before', 'after'], $calls);
    }

    private function receive(string ...$names): void
    {
        Kafka::fake();
        Kafka::shouldReceiveMessages(array_map(
            fn (string $name, int $offset) => new ConsumedMessage('orders', 0, [], ['name' => $name], null, $offset, null),
            $names,
            array_keys($names),
        ));
    }

    private function consumer(): \Junges\Kafka\Consumers\Builder
    {
        return Kafka::consumer(['orders'])->withHandler(function (ConsumerMessage $message) {
            $this->handled[] = $message->getBody()['name'];

            if ($message->getBody()['name'] === 'failing') {
                throw new RuntimeException('fail');
            }
        });
    }
}
