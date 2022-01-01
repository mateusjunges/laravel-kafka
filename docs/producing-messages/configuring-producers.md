---
title: Configuring your kafka producer
weight: 2
---

The producer builder, returned by the `publishOn` call, gives you a series of methods which you can use
to configure your kafka producer options.

### Specifying the topic in which the message should be published
The `onTopic` method specifies the topic in which the message should be published. It accepts a string as argument:

```php
Kafka::publishOn('cluster')->onTopic('your-topic');
```

### Defining configuration options
The `withConfigOption` method sets a `\RdKafka\Conf::class` option. You can check all available options [here][rdkafka_config].
These methods set one config per call, and you can use `withConfigOptions` passing an array of config name and config value
as argument. Here's an example:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('cluster')
    ->withConfigOption('property-name', 'property-value')
    ->withConfigOptions([
        'property-name' => 'property-value'
    ]);
```

While you are developing your application, you can enable debug with the `withDebugEnabled` method.
To disable debug mode, you can use `->withDebugEnabled(false)`, or `withDebugDisabled` methods.

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('cluster')
    ->onTopic('topic')
    ->withConfigOption('property-name', 'property-value')
    ->withConfigOptions([
        'property-name' => 'property-value'
    ])
    ->withDebugEnabled() // To enable debug mode
    ->withDebugDisabled() // To disable debug mode
    ->withDebugEnabled(false) // Also to disable debug mode
```