<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\Committer;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

final class KafkaCommitterTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_can_commit(): void
    {
        $kafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('commit')->once()
            ->andReturnSelf();

        $this->app->bind(KafkaConsumer::class, function () use ($kafkaConsumer) {
            return $kafkaConsumer->getMock();
        });

        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            groupId: 'groupId'
        );

        $conf = new Conf();

        foreach ($config->getConsumerOptions() as $key => $value) {
            $conf->set($key, $value);
        }

        $kafkaCommitter = new Committer(app(KafkaConsumer::class, [
            'conf' => $conf,
        ]));

        $kafkaCommitter->commitMessage(new Message(), true);
    }

    #[Test]
    public function it_can_commit_to_dlq(): void
    {
        $kafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('commit')->once()
            ->andReturnSelf();

        $this->app->bind(KafkaConsumer::class, function () use ($kafkaConsumer) {
            return $kafkaConsumer->getMock();
        });

        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            groupId: 'groupId'
        );

        $conf = new Conf();

        foreach ($config->getConsumerOptions() as $key => $value) {
            $conf->set($key, $value);
        }

        $kafkaCommitter = new Committer(app(KafkaConsumer::class, [
            'conf' => $conf,
        ]));

        $kafkaCommitter->commitDlq(new Message());
    }
}
