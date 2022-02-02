---
title: Custom Committers
weight: 4
---

By default, the committers provided by the `DefaultCommitterFactory` are provided.

To set a custom committer on your consumer, add the committer via a factory that implements the `CommitterFactory` interface:

```php
use \RdKafka\KafkaConsumer;
use \RdKafka\Message;
use \Junges\Kafka\Commit\Contracts\Committer;
use \Junges\Kafka\Commit\Contracts\CommitterFactory;
use \Junges\Kafka\Config\Config;

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

$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->usingCommitterFactory(new MyCommitterFactory())
    ->build();
```