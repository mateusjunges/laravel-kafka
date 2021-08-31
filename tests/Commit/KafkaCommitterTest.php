<?php

namespace Commit;

use Junges\Kafka\Commit\KafkaCommitter;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

class KafkaCommitterTest extends LaravelKafkaTestCase
{
    public function testItCanCommit()
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

        $kafkaCommitter = new KafkaCommitter(app(KafkaConsumer::class, [
            'conf' => $conf,
        ]));

        $kafkaCommitter->commitMessage();
    }

    public function testItCanCommitToDlq()
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

        $kafkaCommitter = new KafkaCommitter(app(KafkaConsumer::class, [
            'conf' => $conf,
        ]));

        $kafkaCommitter->commitDlq();
    }
}
