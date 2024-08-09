---
title: Producing messages
weight: 1
---

To publish your messages to Kafka, you can use the `publish` method, of `Junges\Kafka\Facades\Kafka` class:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publish('broker')->onTopic('topic-name')
```

This method returns a `ProducerBuilder` instance, which contains a few methods to configure your kafka producer. 
The following lines describes these methods.

If you are going to produce a lot of messages to different topics, please use the `asyncPublish` method on the `Junges\Kafka\Facades\Kafka` class:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::asyncPublish('broker')->onTopic('topic-name')
```

The main difference is that the Async Producer is a singleton and will only flush the producer when the application is shutting down, instead of after each send or batch send. 
This reduces the overhead when you want to send a lot of messages in your request handlers. 