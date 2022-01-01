---
title: Configuring consumer options
weight: 6
---

The `ConsumerBuilder` offers you some few configuration options.

### Configuring max messages to be consumed
If you want to consume a limited amount of messages, you can use the `withMaxMessages` method to set the max number of messages to be consumed by a
kafka consumer:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('brokers')->withMaxMessages(2);
```

### Configuring a dead letter queue
In kafka, a Dead Letter Queue (or DLQ), is a simple kafka topic in the kafka cluster which acts as the destination for messages that were not
able to make it to the desired destination due to some error.

To create a `dlq` in this package, you can use the `withDlq` method. If you don't specify the DLQ topic name, it will be created based on the topic you are consuming,
adding the `-dlq` suffix to the topic name.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('brokers')->withDlq();

//Or, specifying the dlq topic name:
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('brokers')->withDlq('your-dlq-topic-name')
```

### Using auto commit
The auto-commit check is called in every poll, and it checks that the time elapsed is greater than the configured time. To enable auto commit,
use the `withAutoCommit` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('brokers')->withAutoCommit();
```


### Setting Kafka configuration options
To set configuration options, you can use two methods: `withOptions`, passing an array of option and option value or, using the `withOption method and
passing two arguments, the option name and the option value.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('brokers')
    ->withOptions([
        'option-name' => 'option-value'
    ]);
// Or:
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('brokers')
    ->withOption('option-name', 'option-value');
```
