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
    public function commitMessage(Message $message, bool $success) : void {
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

### Usage example
If you want to define a new committer for you consumer, you must start by creating a new class that implements the `Committer` interface. 
The `commitMessage` function has a `$success` param, which is true for all messages that were consumed without throwing exceptions or messages which exceptions were handled successfully by the consumer class. So, the following committer will commit only messages that were consumed withtout throwing an exception:

```php
class CustomCommitter implements CommitterContract
{
    public function __construct(private KafkaConsumer $consumer) {}

    public function commitMessage(Message $message, bool $success): void
    {
        if (! $success) {
            return;
        }
        
        $this->consumer->commit($message);
    }

    public function commitDlq(Message $message): void
    {
        $this->consumer->commit($message);
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
            new SuccessCommitter($kafkaConsumer),
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

