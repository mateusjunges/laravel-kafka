---
title: Custom Committers
weight: 4
---

By default, the committers provided by the `DefaultCommitterFactory` are provided.

```+parse
<x-sponsors.request-sponsor/>
```

To set a custom committer on your consumer, add the committer via a factory that implements the `CommitterFactory` interface:

```php
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\Contracts\CommitterFactory;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

class MyCommitter implements Committer
{
    public function commitMessage(Message $message) : void {
        // ...
    }
    
    public function commitDlq(Message $message) : void {
        // ...
    }  
}

class MyCommitterFactory implements CommitterFactory
{
    public function make(KafkaConsumer $kafkaConsumer, Config $config) : Committer {
        // ...
    }
}

$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->usingCommitterFactory(new MyCommitterFactory())
    ->build();
```

### Manual commit support
Custom committers support both automatic and manual commit operations. The `Committer` interface includes:

- `commitMessage(Message $message): void`, used for automatic commits.
- `commitDlq(Message $message): void`, used for dead letter queue commits.
- `commit(mixed $messageOrOffsets = null): void`, used for manual synchronous commits.
- `commitAsync(mixed $messageOrOffsets = null): void`, used for manual asynchronous commits.

When handlers call `$consumer->commit()` or `$consumer->commitAsync()`, these calls are routed through your custom committer, ensuring consistent behavior across all commit types.

### Usage example
If you want to define a new committer for you consumer, you must start by creating a new class that implements the `Committer` interface.
The `commitMessage` method is called in auto commit mode after each message is handled. The following committer commits those messages asynchronously, so the consumer doesn't wait for Kafka to acknowledge each commit:

```php
use Junges\Kafka\Contracts\Committer as CommitterContract;
use Junges\Kafka\Contracts\ConsumerMessage;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicPartition;

class AsyncCommitter implements CommitterContract
{
    public function __construct(private KafkaConsumer $consumer) {}

    public function commitMessage(Message $message): void
    {
        $this->consumer->commitAsync($message);
    }

    public function commitDlq(Message $message): void
    {
        $this->consumer->commitAsync($message);
    }

    public function commit(mixed $messageOrOffsets = null): void
    {
        // Handle manual commits
        if ($messageOrOffsets instanceof ConsumerMessage) {
            $messageOrOffsets = [new TopicPartition(
                $messageOrOffsets->getTopicName(),
                $messageOrOffsets->getPartition(),
                $messageOrOffsets->getOffset() + 1
            )];
        }

        $this->consumer->commit($messageOrOffsets);
    }

    public function commitAsync(mixed $messageOrOffsets = null): void
    {
        // Handle manual async commits
        if ($messageOrOffsets instanceof ConsumerMessage) {
            $messageOrOffsets = [new TopicPartition(
                $messageOrOffsets->getTopicName(),
                $messageOrOffsets->getPartition(),
                $messageOrOffsets->getOffset() + 1
            )];
        }

        $this->consumer->commitAsync($messageOrOffsets);
    }
}
```

After creating your custom committer implementation, you must create a committer factory, which is a simples class that implements the `CommitterFactory` interface, which will be used to provide your custom committer implementation to the consumer class:

```php
class CustomCommitterFactory implements CommitterFactory
{
    public function make(KafkaConsumer $kafkaConsumer, Config $config): CommitterContract
    {
        return new RetryableCommitter(
            new AsyncCommitter($kafkaConsumer),
            new NativeSleeper(),
            $config->getMaxCommitRetries()
        );
    }
}
```

To use this committer implementation, you just need to inform your consumer that you want to use a custom committer class:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer()
    ->usingCommitterFactory(new CustomCommitterFactory())
    ->build();
```

