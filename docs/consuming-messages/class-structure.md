---
title: Consumer classes
weight: 10
---

Consumers can be defined as classes, which are run by the `kafka:consume` artisan command. To create one, use the `make:kafka-consumer` command, which creates the class in the `app/Kafka/Consumers` directory:

```bash
php artisan make:kafka-consumer OrdersConsumer
```

A consumer class extends `Junges\Kafka\KafkaConsumer`. Its properties configure the consumer, and its `handle` method receives each consumed message:

```php
<?php

namespace App\Kafka\Consumers;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\KafkaConsumer;

class OrdersConsumer extends KafkaConsumer
{
    public array $topics = ['orders'];

    public ?string $group = 'orders';

    public int $retries = 3;

    public int $backoff = 1000;

    public string|true|null $dlq = true;

    public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        // Handle your message here
    }
}
```

```+parse
<x-sponsors.request-sponsor/>
```

The consumer class is resolved from the service container, so its constructor can receive any dependency it needs.

### Available properties

| Property | Description |
| --- | --- |
| `$topics` | The topics to consume. |
| `$connection` | The [connection](../advanced-usage/connections.md) to consume from. The default connection is used when it is `null`. |
| `$group` | The consumer group. The group of the connection is used when it is `null`. |
| `$retries` | How many times a failed message is retried before it is handled as failed. See [handling failed messages](handling-failed-messages.md). |
| `$backoff` | How long to wait before each retry, in milliseconds. |
| `$dlq` | The dead letter queue topic. When it is `true`, the name of the first topic followed by `-dlq` is used. |
| `$skipFailedMessages` | Whether failed messages are skipped when there is no dead letter queue, instead of stopping the consumer. |

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

For any option not covered by the properties, override the `configure` method. It receives the consumer builder, with every method described in these docs:

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

To keep the consumer process running permanently in the background, you should use a process monitor such as [supervisor](http://supervisord.org/) to ensure that the consumer does not stop running.

## Supervisor configuration
In production, you need a way to keep your consumer processes running. For this reason, you need to configure a process monitor that can detect when your consumer processes exit and automatically restart them. In addition, process monitors can allow you to specify how many consumer processes you would like to run concurrently. Supervisor is a process monitor commonly used in Linux environments and we will discuss how to configure it in the following documentation.

### Installing supervisor
To install supervisor on Ubuntu, you may use the following command:
```bash
sudo apt-get install supervisor
```

On mac, you can use homebrew:

```bash
brew install supervisor
```

### Configuring supervisor
Supervisor configuration files are typically stored in the `/etc/supervisor/conf.d` directory. Within this directory, you may create any number of configuration files that instruct supervisor how your processes should be monitored. For example, let's create a `orders-consumer.conf` file that starts and monitors our Consumer:

```text
[program:orders-consumer]
directory=/var/www/html
process_name=%(program_name)s_%(process_num)02d
command=php artisan kafka:consume OrdersConsumer
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor-laravel-worker.log
stopwaitsecs=3600
```

#### Starting Supervisor
Once the configuration file has been created, you may update Supervisor configuration and start the processes using the following commands:

```bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl start orders-consumer:*
```
