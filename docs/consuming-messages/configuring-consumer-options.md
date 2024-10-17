---
title: Configuring consumer options
weight: 6
---

The `ConsumerBuilder` offers you some few configuration options.

```+parse
<x-sponsors.request-sponsor/>
```

### Configuring a dead letter queue
In kafka, a Dead Letter Queue (or DLQ), is a simple kafka topic in the kafka cluster which acts as the destination for messages that were not
able to make it to the desired destination due to some error.

To create a `dlq` in this package, you can use the `withDlq` method. If you don't specify the DLQ topic name, it will be created based on the topic you are consuming,
adding the `-dlq` suffix to the topic name.

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->subscribe('topic')->withDlq();

//Or, specifying the dlq topic name:
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->subscribe('topic')->withDlq('your-dlq-topic-name')
```

When your message is sent to the dead letter queue, we will add three header keys to containing information about what happened to that message:

- `kafka_throwable_message`: The exception message
- `kafka_throwable_code`: The exception code
- `kafka_throwable_class_name`: The exception class name.

### Using auto commit
The auto-commit check is called in every poll, and it checks that the time elapsed is greater than the configured time. To enable auto commit,
use the `withAutoCommit` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->withAutoCommit();
```

### Configuring max messages to be consumed
If you want to consume a limited amount of messages, you can use the `withMaxMessages` method to set the max number of messages to be consumed by a
kafka consumer:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->withMaxMessages(2);
```

### Configuring the max time when a consumer can process messages
If you want to consume a limited amount of time, you can use the `withMaxTime` method to set the max number of seconds for
kafka consumer to process messages:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->withMaxTime(3600);
```

### Setting Kafka configuration options
To set configuration options, you can use two methods: `withOptions`, passing an array of option and option value or, using the `withOption method and
passing two arguments, the option name and the option value.

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withOptions([
        'option-name' => 'option-value'
    ]);
// Or:
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withOption('option-name', 'option-value');
```