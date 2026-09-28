<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\Committer;
use Junges\Kafka\Commit\DefaultCommitterFactory;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;

final class CommitterFactoryTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_builds_a_committer_for_the_given_consumer(): void
    {
        $consumer = $this->createStub(KafkaConsumer::class);

        $committer = (new DefaultCommitterFactory)->make($consumer, new Config(broker: 'broker', topics: ['topic']));

        $this->assertEquals(new Committer($consumer), $committer);
    }
}
