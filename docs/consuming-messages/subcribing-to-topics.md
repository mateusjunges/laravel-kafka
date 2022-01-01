---
title: Subscribing to kafka topics
weight: 2
---

With a consumer created, you can subscribe to a kafka topic using the `subscribe` method:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer('brokers')->subscribe('topic');
```

Of course, you can subscribe to more than one topic at once, either using an array of topics or specifying one by one:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer('brokers')->subscribe('topic-1', 'topic-2', 'topic-n');

// Or, using array:
$consumer = Kafka::createConsumer('brokers')->subscribe([
    'topic-1',
    'topic-2',
    'topic-n'
]);
```

### Unsubscribe from a topic

To unsubscribe from a kafka topic, you can use the `unsubcribe` method:

```php
$consumer->unsubscribe('topicX');
```
