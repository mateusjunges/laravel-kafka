---
title: Writing custom loggers
weight: 7
---

Sometimes you need more control over your logging setup. From `v1.10.1` of this package, you can define your own `Logger` implementation. This means that you have the flexibility to log to different types of storage, such as file or cloud-based logging service. 

```+parse
<x-sponsors.request-sponsor/>
```

This can be useful for organizations that need to comply with data privacy regulations, such as the General Data Protection Regulation (GDPR). For example, if an exception occurs and gets logged, it might contain sensitive information such as personally identifiable information (PII). Implementing a custom logger, you can now configure it to automatically redact this information before it gets written to the log.

A `Logger` is any class that implements the `\Junges\Kafka\Contracts\Logger` interface, and it only require that you define a `error` method.

After creating your Logger, you need to [bind it to the Laravel container](https://laravel.com/docs/9.x/container#binding-basics):

```php
$this->app->bind(Logger::class, function ($app) {
    return new MyCustomLogger();
});
```