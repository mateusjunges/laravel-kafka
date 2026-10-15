<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use ArrayIterator;
use Junges\Kafka\Consumers\PartitionLag;
use Junges\Kafka\Facades\Kafka;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;
use RdKafka\KafkaConsumerTopic;
use RdKafka\Metadata;
use RdKafka\Metadata\Collection;
use RdKafka\Metadata\Partition;
use RdKafka\Metadata\Topic;
use RdKafka\TopicPartition;

final class ConnectionLagTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_gets_the_lag_of_a_consumer_group_on_each_partition(): void
    {
        $conf = null;

        $kafkaConsumer = m::mock(KafkaConsumer::class);
        $kafkaConsumer->shouldReceive('newTopic')->with('orders')->andReturn($topic = m::mock(KafkaConsumerTopic::class));
        $kafkaConsumer->shouldReceive('getMetadata')->once()->with(false, $topic, 10000)->andReturn($this->metadata([1, 0, 2]));
        $kafkaConsumer->shouldReceive('getCommittedOffsets')->once()
            ->withArgs(fn (array $partitions, int $timeout) => array_map(fn (TopicPartition $partition) => $partition->getPartition(), $partitions) === [0, 1, 2])
            ->andReturn([
                new TopicPartition('orders', 0, 40),
                // librdkafka returns RD_KAFKA_OFFSET_INVALID for partitions without a committed offset.
                new TopicPartition('orders', 1, -1001),
                new TopicPartition('orders', 2, 100),
            ]);
        $kafkaConsumer->shouldReceive('queryWatermarkOffsets')->times(3)->andReturnUsing(function (string $topic, int $partition, &$low, &$high) {
            [$low, $high] = [[0, 50], [10, 20], [0, 100]][$partition];
        });
        $kafkaConsumer->shouldNotReceive('subscribe');
        $kafkaConsumer->shouldReceive('close')->once();

        $this->app->bind(KafkaConsumer::class, function ($app, array $parameters) use ($kafkaConsumer, &$conf) {
            $conf = $parameters['conf']->dump();

            return $kafkaConsumer;
        });

        $lag = Kafka::connection()->lag('orders-group', ['orders']);

        $this->assertEquals([
            new PartitionLag('orders', 0, committedOffset: 40, lowWatermark: 0, highWatermark: 50, lag: 10),
            new PartitionLag('orders', 1, committedOffset: null, lowWatermark: 10, highWatermark: 20, lag: null),
            new PartitionLag('orders', 2, committedOffset: 100, lowWatermark: 0, highWatermark: 100, lag: 0),
        ], $lag);
        $this->assertSame('orders-group', $conf['group.id']);
        $this->assertSame('false', $conf['enable.auto.commit']);
    }

    #[Test]
    public function it_returns_no_lag_when_the_topics_have_no_partitions(): void
    {
        $kafkaConsumer = m::mock(KafkaConsumer::class);
        $kafkaConsumer->shouldReceive('newTopic')->andReturn(m::mock(KafkaConsumerTopic::class));
        $kafkaConsumer->shouldReceive('getMetadata')->andReturn($this->metadata([]));
        $kafkaConsumer->shouldNotReceive('getCommittedOffsets');
        $kafkaConsumer->shouldReceive('close')->once();

        $this->app->bind(KafkaConsumer::class, fn () => $kafkaConsumer);

        $this->assertSame([], Kafka::connection()->lag('orders-group', ['missing']));
    }

    /** @param list<int> $partitionIds */
    private function metadata(array $partitionIds): Metadata
    {
        $partitions = array_map(fn (int $id) => m::mock(Partition::class, ['getId' => $id]), $partitionIds);

        $topic = m::mock(Topic::class);
        $topic->shouldReceive('getPartitions')->andReturn($this->collection($partitions));

        $metadata = m::mock(Metadata::class);
        $metadata->shouldReceive('getTopics')->andReturn($this->collection([$topic]));

        return $metadata;
    }

    private function collection(array $items): Collection
    {
        $iterator = new ArrayIterator($items);

        $collection = m::mock(Collection::class);
        $collection->shouldReceive('rewind')->andReturnUsing($iterator->rewind(...));
        $collection->shouldReceive('valid')->andReturnUsing($iterator->valid(...));
        $collection->shouldReceive('current')->andReturnUsing($iterator->current(...));
        $collection->shouldReceive('key')->andReturnUsing($iterator->key(...));
        $collection->shouldReceive('next')->andReturnUsing($iterator->next(...));

        return $collection;
    }
}
