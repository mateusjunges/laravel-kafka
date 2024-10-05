---
title: Producing messages
weight: 1
---

To publish your messages to Kafka, you can use the publishOn method, of `Junges\Kafka\Facades\Kafka` class:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic')
```

This method returns a `ProducerBuilder` instance, which contains a few methods to configure your kafka producer. 
The following lines describes these methods.