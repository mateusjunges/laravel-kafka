---
title: Using consumers with Laravel Telescope
weight: 11
---

If your application uses [Laravel Telescope](https://laravel.com/docs/telescope), you should disable Telescope for your Kafka consumers.

```+parse
<x-sponsors.request-sponsor/>
```

Telescope keeps the entries it records during an Artisan command in memory, and only stores them once the command terminates. Kafka consumers are long running processes that never terminate on their own, so every event, query and log entry recorded while consuming messages stays in memory. On a busy topic, this makes the consumer memory usage grow until the process crashes with an `Allowed memory size exhausted` error. Telescope ignores the `queue:work` and `horizon` commands by default for the same reason.

To disable Telescope for your consumers, add their command names to the `ignore_commands` option of your `config/telescope.php` file. This includes the `kafka:consume` command, which runs your [consumer classes](class-structure.md), and any command you have written yourself to run a consumer:

```php
'ignore_commands' => [
    'kafka:consume',
    'consume:my-topic',
],
```

Telescope compares the command names exactly, so each consumer command must be listed.

The same applies to anything else that keeps records in memory for the lifetime of the process, such as the database query log enabled with `DB::enableQueryLog()`. Make sure these are disabled in your consumer processes.
