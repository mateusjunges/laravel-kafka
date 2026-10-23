---
title: Writing custom loggers
weight: 7
---

Consumers log the errors that happen while consuming messages, such as failed messages and commit errors. By default, they are written to the standard output, as JSON. You can replace the logger with your own implementation, to log to a different storage, or to redact information from the logs.

```+parse
<x-sponsors.request-sponsor/>
```

This can be useful for organizations that need to comply with data privacy regulations, such as the General Data Protection Regulation (GDPR). For example, if an exception occurs and gets logged, it might contain personally identifiable information (PII). A custom logger can redact this information before it gets written to the log.

A logger is any class that implements the `\Junges\Kafka\Contracts\Logger` interface, which requires two methods:

- `error()` logs errors consuming a message. It receives the Kafka message, the exception, and a prefix describing where the error happened.
- `commitError()` logs errors committing offsets, after which the consumer throws the exception. It receives the argument given to `commit()` or `commitAsync()`, which is a `ConsumerMessage`, an `RdKafka\Message`, a list of `RdKafka\TopicPartition`, or `null` when the offsets of the current assignment were being committed, and the exception.

```php
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Logger;
use RdKafka\Message;
use Throwable;

class MyCustomLogger implements Logger
{
    public function error(Message $message, ?Throwable $e = null, string $prefix = 'ERROR'): void
    {
        // Log the error, redacting the message payload if needed.
    }

    public function commitError(ConsumerMessage|Message|array|null $messageOrOffsets, Throwable $e): void
    {
        // Log the commit failure.
    }
}
```

`RdKafka\TopicPartition` objects have no public properties, so they are logged as empty objects when given to a JSON formatter as they are. Read their topic, partition and offset through `getTopic()`, `getPartition()` and `getOffset()` instead. The default logger logs the topic, partition and offset being committed. For messages, that is the offset right after them, which is the one sent to Kafka.

After creating your logger, [bind it in the service container](https://laravel.com/docs/container#binding-basics), in the `register` method of a service provider:

```php
$this->app->singleton(\Junges\Kafka\Contracts\Logger::class, MyCustomLogger::class);
```
