---
title: Producing message batch to kafka
weight: 6
---

You can publish multiple messages at the same time
First, make sure you have created `MessageBatch`.
Then create as many messages as you want and push them to `MesageBatch` instance.
Finally, create your producer, call `ProducerBuilder::sendBatch` and pass `MessageBatch` instance.
This is helpful when you persist messages in storage before publishing (e.g. TransactionalOutbox Pattern).
```php
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Producers\MessageBatch;
use Junges\Kafka\Message\Message;

$message = new Message(
    headers: ['header-key' => 'header-value'],
    body: ['key' => 'value'],
    key: 'kafka key here'  
)

$messageBatch = new MessageBatch();
$messageBatch->push($message);
$messageBatch->push($message);
$messageBatch->push($message);
$messageBatch->push($message);

/** @var \Junges\Kafka\Producers\ProducerBuilder $producer */
$producer = Kafka::publishOn('topic')
    ->withConfigOptions(['key' => 'value']);

$producer->sendBatch($messageBatch);
```