<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Conf;
use RdKafka\Message;
use RdKafka\Producer as KafkaProducer;

final class ConsumerDlqProducerTest extends LaravelKafkaTestCase
{
    private int $producersCreated = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConsumerWithMessage($this->message());
        $this->countKafkaProducerCreations();
    }

    #[Test]
    public function it_does_not_create_a_producer_when_no_dead_letter_queue_is_configured(): void
    {
        $consumer = $this->buildConsumer();

        $consumer->consume();

        $this->assertSame(1, $consumer->consumedMessagesCount());
        $this->assertSame(0, $this->producersCreated);
    }

    #[Test]
    public function it_creates_a_producer_when_a_dead_letter_queue_is_configured(): void
    {
        $consumer = $this->buildConsumer(dlq: 'test-dlq');

        $consumer->consume();

        $this->assertSame(1, $consumer->consumedMessagesCount());
        $this->assertSame(1, $this->producersCreated);
    }

    private function buildConsumer(?string $dlq = null): MessageConsumer
    {
        $builder = Kafka::consumer(['test'])
            ->withHandler(static function (ConsumerMessage $message): void {})
            ->withMaxMessages(1);

        if ($dlq !== null) {
            $builder->withDlq($dlq);
        }

        return $builder->build();
    }

    private function countKafkaProducerCreations(): void
    {
        $conf = new Conf;
        $conf->set('log_level', '0');
        $topic = (new KafkaProducer($conf))->newTopic('test-dlq');

        $mockedKafkaProducer = m::mock(KafkaProducer::class)
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->shouldReceive('newTopic')
            ->andReturn($topic)
            ->getMock();

        $this->app->bind(KafkaProducer::class, function () use ($mockedKafkaProducer): KafkaProducer {
            $this->producersCreated++;

            return $mockedKafkaProducer;
        });
    }

    private function message(): Message
    {
        $message = new Message;
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        return $message;
    }
}
