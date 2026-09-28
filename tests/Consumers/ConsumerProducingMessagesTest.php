<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Consumers\MessageHandler;
use Junges\Kafka\Contracts\Consumer as ConsumerContract;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;
use RdKafka\KafkaConsumerTopic;
use RdKafka\Message;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;

final class ConsumerProducingMessagesTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_flushes_the_messages_published_by_the_handler_before_storing_the_offset(): void
    {
        $producer = $this->mockRdKafkaProducer();
        $producer->shouldReceive('flush')->once()->globally()->ordered()->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR);

        $topic = m::mock(KafkaConsumerTopic::class);
        $topic->shouldReceive('offsetStore')->once()->globally()->ordered();

        $this->mockConsumerReceivingAMessage($topic);

        $this->consume(true, function (ConsumerMessage $message) {
            Kafka::publish('orders-processed')->withBody($message->getBody())->send();
        });
    }

    #[Test]
    public function it_does_not_flush_again_when_the_handler_already_flushed(): void
    {
        $producer = $this->mockRdKafkaProducer();
        $producer->shouldReceive('flush')->once()->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR);

        $this->mockConsumerReceivingAMessage(m::mock(KafkaConsumerTopic::class, ['offsetStore' => null]));

        $this->consume(true, function (ConsumerMessage $message) {
            Kafka::publishSync('orders-processed')->withBody($message->getBody())->send();
        });
    }

    #[Test]
    public function it_flushes_the_messages_published_by_the_handler_before_committing_manually(): void
    {
        $producer = $this->mockRdKafkaProducer();
        $producer->shouldReceive('flush')->once()->globally()->ordered()->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR);

        $consumer = $this->mockConsumerReceivingAMessage();
        $consumer->shouldReceive('commit')->once()->globally()->ordered();

        $this->consume(false, function (ConsumerMessage $message, ConsumerContract $consumer) {
            Kafka::publish('orders-processed')->withBody($message->getBody())->send();

            $consumer->commit($message);
        });
    }

    private function consume(bool $autoCommit, callable $handler): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['orders'],
            groupId: 'group',
            handler: new MessageHandler($handler(...)),
            maxMessages: 1,
            autoCommit: $autoCommit,
        );

        (new Consumer($config, new JsonDeserializer))->consume();
    }

    private function mockRdKafkaProducer(): MockInterface
    {
        $producer = m::mock(KafkaProducer::class);
        $producer->shouldReceive('newTopic')->andReturn(m::mock(ProducerTopic::class, ['producev' => null]));
        $producer->shouldReceive('poll');

        $this->app->bind(KafkaProducer::class, fn () => $producer);

        return $producer;
    }

    private function mockConsumerReceivingAMessage(?KafkaConsumerTopic $topic = null): MockInterface
    {
        $message = new Message;
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message->topic_name = 'orders';
        $message->partition = 0;
        $message->offset = 0;
        $message->key = null;
        $message->payload = '{"id":1}';
        $message->headers = [];

        $consumer = $this->mockKafkaConsumer();
        $consumer->shouldReceive('subscribe');
        $consumer->shouldReceive('consume')->andReturn($message);

        if ($topic !== null) {
            $consumer->shouldReceive('newTopic')->andReturn($topic);
        }

        $this->app->bind(KafkaConsumer::class, fn () => $consumer);

        return $consumer;
    }
}
