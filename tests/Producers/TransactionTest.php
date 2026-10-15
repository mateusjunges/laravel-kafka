<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Producers;

use Junges\Kafka\Connection;
use Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException;
use Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException;
use Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use LogicException;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaErrorException;
use RuntimeException;

final class TransactionTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_commits_the_transaction_and_returns_the_result_of_the_callback(): void
    {
        $producer = $this->mockTransactionalProducer();
        $producer->shouldReceive('beginTransaction')->once()->globally()->ordered();
        $producer->shouldReceive('produce')->twice()->globally()->ordered();
        $producer->shouldReceive('commitTransaction')->once()->globally()->ordered();
        $producer->shouldNotReceive('abortTransaction');

        $result = Kafka::connection()->transaction(function (Connection $connection) {
            $connection->publish('ledger')->withBody(['amount' => 10])->send();
            $connection->publish('notifications')->withBody(['sent' => true])->send();

            return 'done';
        });

        $this->assertSame('done', $result);
    }

    #[Test]
    public function it_aborts_the_transaction_when_the_callback_throws(): void
    {
        $producer = $this->mockTransactionalProducer();
        $producer->shouldReceive('beginTransaction')->once();
        $producer->shouldNotReceive('commitTransaction');
        $producer->shouldReceive('abortTransaction')->once();

        $this->expectExceptionObject($exception = new RuntimeException('Failed'));

        Kafka::connection()->transaction(fn () => throw $exception);
    }

    #[Test]
    public function it_retries_committing_the_transaction_when_the_error_is_retriable(): void
    {
        $producer = $this->mockTransactionalProducer();
        $producer->shouldReceive('beginTransaction')->once();
        $producer->shouldReceive('commitTransaction')->times(3)->andReturnUsing($this->failing(2, fn () => $this->retriable()));
        $producer->shouldNotReceive('abortTransaction');

        $calls = 0;

        Kafka::connection()->transaction(function () use (&$calls) {
            $calls++;
        });

        $this->assertSame(1, $calls);
    }

    #[Test]
    public function it_runs_the_callback_again_when_the_transaction_must_be_aborted(): void
    {
        $producer = $this->mockTransactionalProducer();
        $producer->shouldReceive('beginTransaction')->twice();
        $producer->shouldReceive('commitTransaction')->twice()->andReturnUsing($this->failing(1, fn () => $this->abortRequired()));
        $producer->shouldReceive('abortTransaction')->once();

        $calls = 0;

        Kafka::connection()->transaction(function () use (&$calls) {
            $calls++;
        });

        $this->assertSame(2, $calls);
    }

    #[Test]
    public function it_gives_up_when_the_transaction_must_be_aborted_on_every_attempt(): void
    {
        $producer = $this->mockTransactionalProducer();
        $producer->shouldReceive('beginTransaction')->twice();
        $producer->shouldReceive('commitTransaction')->twice()->andThrow($this->abortRequired());
        $producer->shouldReceive('abortTransaction')->twice();

        $this->expectException(TransactionShouldBeAbortedException::class);

        Kafka::connection()->transaction(fn () => null, attempts: 2);
    }

    #[Test]
    public function it_does_not_abort_after_a_fatal_error(): void
    {
        $producer = $this->mockTransactionalProducer();
        $producer->shouldReceive('beginTransaction')->once();
        $producer->shouldReceive('commitTransaction')->once()->andThrow(
            TransactionFatalErrorException::new(new KafkaErrorException('Fatal', 1, 'fatal', true, false, false))
        );
        $producer->shouldNotReceive('abortTransaction');

        $this->expectException(TransactionFatalErrorException::class);

        Kafka::connection()->transaction(fn () => null);
    }

    #[Test]
    public function it_requires_a_transactional_id(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Transactions require a [transactional.id] in the producer options of the [default] Kafka connection.');

        Kafka::connection()->transaction(fn () => null);
    }

    #[Test]
    public function transactions_can_be_used_with_the_kafka_fake(): void
    {
        config(['kafka.connections.default.producer.options' => ['transactional.id' => 'app']]);

        Kafka::fake();

        Kafka::connection()->transaction(function (Connection $connection) {
            $connection->publish('ledger')->withBody(['amount' => 10])->send();
        });

        Kafka::assertPublishedOn('ledger');
    }

    private function mockTransactionalProducer(): MockInterface
    {
        config(['kafka.connections.default.producer.options' => ['transactional.id' => 'app']]);

        $producer = m::mock(Producer::class);
        $producer->shouldReceive('flush')->byDefault();

        $this->app->bind(Producer::class, fn () => $producer);

        return $producer;
    }

    /** Throw the exception the given number of times, and then succeed. */
    private function failing(int $times, callable $exception): callable
    {
        return function () use (&$times, $exception) {
            if ($times-- > 0) {
                throw $exception();
            }
        };
    }

    private function retriable(): TransactionShouldBeRetriedException
    {
        return TransactionShouldBeRetriedException::new(new KafkaErrorException('Timed out', 1, 'timed out', false, true, false));
    }

    private function abortRequired(): TransactionShouldBeAbortedException
    {
        return TransactionShouldBeAbortedException::new(new KafkaErrorException('Abort', 1, 'abort', false, false, true));
    }
}
