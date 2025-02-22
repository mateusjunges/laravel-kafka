---
title: Subscribing to kafka topics
weight: 2
---

```+parse
<x-sponsors.request-sponsor/>
```

With a consumer created, you can subscribe to a kafka topic using the `subscribe` method:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer()->subscribe('topic');
```

Of course, you can subscribe to more than one topic at once, either using an array of topics or specifying one by one:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer()->subscribe('topic-1', 'topic-2', 'topic-n');

// Or, using array:
$consumer = Kafka::consumer()->subscribe([
    'topic-1',
    'topic-2',
    'topic-n'
]);
```