---
title: Using the built-in consumer command
weight: 8
---

This library provides you a built-in consumer command, which you can use to consume messages.

To use this command, you must create a `Handler` class, which extends `Junges\Kafka\Contracts\Handler`.

Then, just use the following command:

```bash
php artisan kafka:consume --consumer=\\App\\Path\\To\\Your\\Consumer --topics=topic-to-consume
```

> Note: The default brokers of the default consumer defined in `config/kafka.php` will be used.
