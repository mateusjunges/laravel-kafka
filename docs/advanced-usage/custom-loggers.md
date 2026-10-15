---
title: Writing custom loggers
weight: 7
---

Consumers log the errors that happen while consuming messages, such as failed messages and commit errors. By default, they are written to the standard output, as JSON. You can replace the logger with your own implementation, to log to a different storage, or to redact information from the logs.

```+parse
<x-sponsors.request-sponsor/>
```

This can be useful for organizations that need to comply with data privacy regulations, such as the General Data Protection Regulation (GDPR). For example, if an exception occurs and gets logged, it might contain personally identifiable information (PII). A custom logger can redact this information before it gets written to the log.

A logger is any class that implements the `\Junges\Kafka\Contracts\Logger` interface, which requires an `error` method receiving the Kafka message, the exception, and a prefix describing where the error happened.

After creating your logger, [bind it in the service container](https://laravel.com/docs/container#binding-basics), in the `register` method of a service provider:

```php
$this->app->singleton(\Junges\Kafka\Contracts\Logger::class, MyCustomLogger::class);
```
