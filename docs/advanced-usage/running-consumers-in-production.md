---
title: Running consumers in production
weight: 3
---

Consumers are long running processes. In production, they need a process monitor that keeps them running, restarts them when they exit, and stops them gracefully during deployments.

```+parse
<x-sponsors.request-sponsor/>
```

## Supervisor

Supervisor is a process monitor commonly used in Linux environments. To install it on Ubuntu, you may use the following command:

```bash
sudo apt-get install supervisor
```

On macOS, you can use Homebrew:

```bash
brew install supervisor
```

Supervisor configuration files are typically stored in the `/etc/supervisor/conf.d` directory. Within this directory, you may create any number of configuration files that instruct Supervisor how your processes should be monitored. For example, let's create an `orders-consumer.conf` file that starts and monitors a [consumer class](../consuming-messages/class-structure.md):

```text
[program:orders-consumer]
directory=/var/www/html
process_name=%(program_name)s_%(process_num)02d
command=php artisan kafka:consume OrdersConsumer
numprocs=3
autostart=true
autorestart=true
startsecs=1
startretries=10
stopwaitsecs=60
redirect_stderr=true
stdout_logfile=/var/log/supervisor/orders-consumer.log
```

Once the configuration file has been created, update the Supervisor configuration and start the processes:

```bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl start orders-consumer:*
```

### How many processes to run

Consumers of the same group share the partitions of the topics they consume, and each partition is consumed by a single consumer of the group. Running more consumer processes than partitions leaves the extra processes idle, so `numprocs` should not exceed the number of partitions.

### Stopping gracefully

When Supervisor stops a process, it sends a `SIGTERM` signal, and the consumer finishes the message it is handling before exiting, see [graceful shutdown](graceful-shutdown.md). Supervisor waits `stopwaitsecs` seconds before killing the process, so set it higher than the longest time your handler may take to process a message, including the [retries](../consuming-messages/handling-failed-messages.md#retrying-failed-messages) of a failed message and the wait between them.

### Restarting after failures

When a message fails and there is no dead letter queue, the consumer stops and exits with an error, so the message is consumed again once the process is restarted. Supervisor considers a process that exits within `startsecs` seconds of starting as a failed start, and gives up on it after `startretries` failed starts. Keep `startsecs` low, so a consumer that stops on a failed message is restarted, and monitor the logs, since a message that always fails stops the consumer every time it is consumed.

## Restarting consumers after deployments

Consumers load your application code once, when they start, so they must be restarted after deploying new code. The `kafka:restart-consumers` command asks every running consumer to stop gracefully once it finishes the message it is handling, and your process monitor starts them again:

```bash
php artisan kafka:restart-consumers
```

To restart only some consumers, pass their names. [Consumer classes](../consuming-messages/class-structure.md#naming-consumers) are named after their class by default, and can be given by their name in the `App\Kafka\Consumers` namespace, like with the `kafka:consume` command:

```bash
php artisan kafka:restart-consumers OrdersConsumer PaymentsConsumer
```

The command stores the restart time in the cache, and consumers check it every second. All consumers must share the cache store defined by the `cache_driver` key of the `config/kafka.php` file, which defaults to your application's cache store, so it can't be a store local to each server, such as `file` or `array`, when consumers run on several servers.

## Limiting memory usage

Long running PHP processes may slowly accumulate memory. To keep it in check, make consumers stop after a number of messages or an amount of time, and let the process monitor start them again:

```bash
php artisan kafka:consume OrdersConsumer --max-messages=10000 --max-time=3600
```

## Producers in long running processes

Messages published with `Kafka::publish()` are queued and delivered in the background. They are flushed when the application terminates, but long running processes don't terminate after each unit of work:

- **Consumers** flush the messages published while handling a message before its offset is stored or committed.
- **Queue workers** flush them after each job.
- **Laravel Octane** flushes them when each request terminates. The Kafka manager is resolved again on every request, which creates a new producer, and its broker connections, for each request. To reuse the same producer for every request handled by a worker, add `Junges\Kafka\Factory::class` to the `warm` array of your `config/octane.php` file.
- **Other long running commands** should call `Kafka::flush()` whenever they need their messages to be delivered, as they are only flushed when the command exits.
