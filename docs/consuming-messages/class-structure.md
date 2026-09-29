---
title: Consumer classes
weight: 10
---

Consumers can be defined as classes, which are run by the `kafka:consume` artisan command. To create one, use the `make:kafka-consumer` command, which creates the class in the `app/Kafka/Consumers` directory:

```bash
php artisan make:kafka-consumer OrdersConsumer
```

A consumer class extends `Junges\Kafka\KafkaConsumer`. Its methods configure the consumer, and its `handle` method receives each consumed message:

```php
<?php

namespace App\Kafka\Consumers;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\KafkaConsumer;

class OrdersConsumer extends KafkaConsumer
{
    public function topics(): array
    {
        return [config('kafka.topics.orders')];
    }

    public function group(): ?string
    {
        return 'orders';
    }

    public function retries(): int
    {
        return 3;
    }

    public function backoff(): int
    {
        return 1000;
    }

    public function dlq(): string|true|null
    {
        return true;
    }

    public function handle(ConsumerMessage $message, Consumer $consumer): void
    {
        // Handle your message here
    }
}
```

```+parse
<x-sponsors.request-sponsor/>
```

The consumer class is resolved from the service container, so its constructor can receive any dependency it needs.

### Available methods

Only `topics` and `handle` are required. The other methods have defaults, and can be overridden when needed:

| Method | Description |
| --- | --- |
| `topics()` | The topics to consume. |
| `connection()` | The [connection](../advanced-usage/connections.md) to consume from. Defaults to `null`, which uses the default connection. |
| `group()` | The consumer group. Defaults to `null`, which uses the group of the connection. |
| `retries()` | How many times a failed message is retried before it is handled as failed. Defaults to `0`. See [handling failed messages](handling-failed-messages.md). |
| `backoff()` | How long to wait before each retry, in milliseconds. It can also return an array, to wait for a different time before each retry, like `[1000, 5000, 10000]`. Defaults to `0`. |
| `dlq()` | The dead letter queue topic. When it returns `true`, the name of the first topic followed by `-dlq` is used. Defaults to `null`, which disables the dead letter queue. |
| `skipFailedMessages()` | Whether failed messages are skipped when there is no dead letter queue, instead of stopping the consumer. Defaults to `false`. |

To be notified when a message is handled as failed, override the `failed` method. See [handling failed messages](handling-failed-messages.md#being-notified-of-failed-messages).

### Naming consumers

Every consumer has a name, which identifies it in [events](../advanced-usage/events.md) and when [restarting consumers](../advanced-usage/running-consumers-in-production.md#restarting-consumers-after-deployments). Consumer classes are named after their class by default. To use another name, override the `name` method:

```php
public function name(): string
{
    return 'orders';
}
```

When building a consumer yourself, use the `withName` method of the consumer builder. Consumers without a name are named after the topics they consume, separated by commas:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withName('orders')
    ->withHandler(new OrderHandler)
    ->build();
```

The consumer passed to handlers, callbacks and events exposes its name, along with its connection, group and topics, through the `getName`, `getConnectionName`, `getGroupId` and `getTopics` methods.

### Middlewares

The `middleware` method returns the [middlewares](../advanced-usage/middlewares.md) each message goes through before reaching the `handle` method:

```php
public function middleware(): array
{
    return [
        new LogMessages,
        function (ConsumerMessage $message, callable $next) {
            return $next($message);
        },
    ];
}
```

### Configuring the consumer builder

For any option not covered by the other methods, override the `configure` method. It receives the consumer builder, with every method described in these docs:

```php
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Message\Deserializers\AvroDeserializer;

public function configure(Builder $builder): Builder
{
    return $builder
        ->withOption('max.poll.interval.ms', 600000)
        ->usingDeserializer(app(AvroDeserializer::class));
}
```

### Running the consumer

Use the `kafka:consume` command, passing the consumer class name. Classes in the `App\Kafka\Consumers` namespace can be referenced by their name only, and any other class by its fully qualified name:

```bash
php artisan kafka:consume OrdersConsumer
```

The command accepts the following options:

| Option | Description |
| --- | --- |
| `--max-messages` | Stop after handling the given number of messages. |
| `--max-time` | Stop after the given number of seconds. |
| `--stop-when-empty` | Stop once there are no messages left in the assigned partitions. |

You can also build the consumer yourself, for instance to run it from your own command, with the `consumerFor` method of the `Kafka` facade:

```php
use App\Kafka\Consumers\OrdersConsumer;
use Junges\Kafka\Facades\Kafka;

Kafka::consumerFor(OrdersConsumer::class)->build()->consume();
```

To keep the consumer process running permanently in the background, use a process monitor such as Supervisor. See [running consumers in production](../advanced-usage/running-consumers-in-production.md) for how to configure it, and how to restart consumers after deployments.
