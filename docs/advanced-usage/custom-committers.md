---
title: Custom Committers
weight: 4
---

In auto commit mode, the consumer stores the offset of each message after it is processed, and librdkafka commits the stored offsets in the background. Committers are used when handlers commit offsets themselves, by calling the `commit` or `commitAsync` methods of the consumer, usually in [manual commit](manual-commit.md) mode.

```+parse
<x-sponsors.request-sponsor/>
```

The `Junges\Kafka\Contracts\Committer` interface has two methods:

- `commit(mixed $messageOrOffsets = null): void`, used for synchronous commits.
- `commitAsync(mixed $messageOrOffsets = null): void`, used for asynchronous commits.

Both receive what the handler passed to the consumer: nothing, to commit the offsets of the current assignment, a `Junges\Kafka\Contracts\ConsumerMessage` or `RdKafka\Message`, or an array of `RdKafka\TopicPartition`.

### Usage example

The following committer retries synchronous commits while the consumer group is rebalancing:

```php
use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\Contracts\ConsumerMessage;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\TopicPartition;

class RetryingCommitter implements Committer
{
    public function __construct(private KafkaConsumer $consumer) {}

    public function commit(mixed $messageOrOffsets = null): void
    {
        retry(3, fn () => $this->consumer->commit($this->offsets($messageOrOffsets)), 100, function (Exception $exception) {
            return $exception->getCode() === RD_KAFKA_RESP_ERR_REBALANCE_IN_PROGRESS;
        });
    }

    public function commitAsync(mixed $messageOrOffsets = null): void
    {
        $this->consumer->commitAsync($this->offsets($messageOrOffsets));
    }

    private function offsets(mixed $messageOrOffsets): mixed
    {
        if (! $messageOrOffsets instanceof ConsumerMessage) {
            return $messageOrOffsets;
        }

        return [new TopicPartition(
            $messageOrOffsets->getTopicName(),
            $messageOrOffsets->getPartition(),
            $messageOrOffsets->getOffset() + 1
        )];
    }
}
```

To use it, create a committer factory, which is a class that implements the `Junges\Kafka\Contracts\CommitterFactory` interface, and pass it to the consumer:

```php
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\Contracts\CommitterFactory;
use Junges\Kafka\Facades\Kafka;
use RdKafka\KafkaConsumer;

class RetryingCommitterFactory implements CommitterFactory
{
    public function make(KafkaConsumer $kafkaConsumer, Config $config): Committer
    {
        return new RetryingCommitter($kafkaConsumer);
    }
}

$consumer = Kafka::consumer(['orders'])
    ->withManualCommit()
    ->usingCommitterFactory(new RetryingCommitterFactory)
    ->withHandler(function ($message, $consumer) {
        // ...
        $consumer->commit($message);
    })
    ->build();
```
