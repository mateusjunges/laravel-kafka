<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Handlers;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Handlers\RetryableHandler;
use Junges\Kafka\Handlers\RetryStrategies\DefaultRetryStrategy;
use Junges\Kafka\Tests\FailingHandler;
use Junges\Kafka\Tests\Fakes\FakeSleeper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RetryableHandlerTest extends TestCase
{
    #[Test]
    public function it_passes_when_no_exception_occurred(): void
    {
        $failingHandler = new FailingHandler(0, new RuntimeException('test'));
        $handler = new RetryableHandler($failingHandler(...), new DefaultRetryStrategy, new FakeSleeper);

        $messageMock = $this->createMock(ConsumerMessage::class);
        $handler($messageMock);

        $this->assertSame(1, $failingHandler->getTimesInvoked());
    }

    #[Test]
    public function it_does_retries_on_exception(): void
    {
        $failingHandler = new FailingHandler(4, new RuntimeException('test'));
        $sleeper = new FakeSleeper;
        $handler = new RetryableHandler($failingHandler(...), new DefaultRetryStrategy, $sleeper);

        $messageMock = $this->createMock(ConsumerMessage::class);
        $handler($messageMock);

        $this->assertSame(5, $failingHandler->getTimesInvoked());
        $this->assertEquals([1e6, 2e6, 4e6, 8e6], $sleeper->getSleeps());
    }

    #[Test]
    public function it_bubbles_exception_when_retries_exceeded(): void
    {
        $failingHandler = new FailingHandler(100, new RuntimeException('test'));
        $sleeper = new FakeSleeper;
        $handler = new RetryableHandler($failingHandler(...), new DefaultRetryStrategy, $sleeper);

        $messageMock = $this->createMock(ConsumerMessage::class);

        try {
            $handler($messageMock);

            $this->fail('Handler passed but a \RuntimeException is expected.');
        } catch (RuntimeException) {
            $this->assertSame(7, $failingHandler->getTimesInvoked());
            $this->assertEquals([1e6, 2e6, 4e6, 8e6, 16e6, 32e6], $sleeper->getSleeps());
        }
    }
}
