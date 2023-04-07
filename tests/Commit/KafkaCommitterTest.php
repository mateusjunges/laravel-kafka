<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\Committer;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

final class KafkaCommitterTest extends LaravelKafkaTestCase
{
    public function testItCanCommit(): void
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

    public function testItCanCommitToDlq(): void
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
