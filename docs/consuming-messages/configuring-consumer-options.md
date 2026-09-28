---
title: Configuring consumer options
weight: 6
---

The consumer builder, returned by `Kafka::consumer()`, offers the following configuration options. Options shared by every consumer of a Kafka cluster belong in the [connection configuration](../advanced-usage/connections.md) instead.

```+parse
<x-sponsors.request-sponsor/>
```

### Configuring a dead letter queue
In kafka, a Dead Letter Queue (or DLQ), is a simple kafka topic in the kafka cluster which acts as the destination for messages that were not
able to make it to the desired destination due to some error.

To create a `dlq` in this package, you can use the `withDlq` method. If you don't specify the DLQ topic name, it will be created based on the topic you are consuming,
adding the `-dlq` suffix to the topic name.

Failed messages are published to the dead letter queue with the producer options of the consumer connection, and flushed right away. If the dead letter queue can't be reached, the consumer stops without committing the offset of the message, so it is not lost.

Without a dead letter queue, the consumer stops when a message fails, without committing its offset. See [handling failed messages](handling-failed-messages.md) for the available options.

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->subscribe('topic')->withDlq();

//Or, specifying the dlq topic name:
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->subscribe('topic')->withDlq('your-dlq-topic-name')
```

When your message is sent to the dead letter queue, we will add three header keys to containing information about what happened to that message:

- `kafka_throwable_message`: The exception message
- `kafka_throwable_code`: The exception code
- `kafka_throwable_class_name`: The exception class name.

#### Adding context metadata to dead letter queue

Sometimes you need additional information (context) in dead letter queue messages. To enrich DLQ message header with custom metadata (e.g. IDs, correlation keys, retry info), throw an exception that implements the `Junges\Kafka\Contracts\ContextAware` interface. 

The consumer will merge into the message headers:

- Original message headers (if any)
- Throwable headers as defined above:
  - `kafka_throwable_message`
  - `kafka_throwable_code`
  - `kafka_throwable_class_name`
- Normalized context from any `ContextAware` exceptions.

Example custom exception:

```php
use Junges\Kafka\Contracts\ContextAware;
use RuntimeException;
use Throwable;

class OrderProcessingException extends RuntimeException implements ContextAware
{
    public function __construct(
        private array $context,
        string $message = 'Order processing failed',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
```

Using it inside a consumer handler:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->subscribe('orders')
    ->withDlq()          // DLQ topic will default to "orders-dlq"
    ->withHandler(function($message) {
        $payload = $message->getBody();

        // Simulate failure
        throw new OrderProcessingException([
            'x-order-id' => (string)($payload['order_id'] ?? 'unknown'),
            'x-user-id' => (string)($payload['user_id'] ?? 'unknown'),
            'x-retry-count' => '3',
        ]);
    })
    ->build();

$consumer->consume();
```

Resulting DLQ headers (example):

```php
[
  'kafka_throwable_message' => 'Order processing failed',
  'kafka_throwable_code' => 0,
  'kafka_throwable_class_name' => OrderProcessingException::class,
  'x-order-id' => '42',
  'x-user-id' => '7',
  'x-retry-count' => '3',
]
```

```+parse
<x-docs.tip title="Hot tip!">
Header values must be strings. Arrays/objects/numbers as well as empty string keys are ignored. Any headers on the original message are preserved unless overwritten.
</x-docs.tip>
```

### Commit modes: Auto vs Manual
The package supports two commit modes for controlling when message offsets are committed to Kafka. Both deliver every message at least once, since failed messages are never committed unless they are sent to a dead letter queue or skipped on purpose.

#### Auto Commit (Default)
With auto commit, the offset of each message is stored after your handler processes it, and librdkafka commits the stored offsets in the background. This is the default and simplest mode:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withHandler(function ($message, $consumer) {
        // Process your message. Its offset is stored once the handler returns.
    });
```

The `auto_commit` key of the connection defines whether consumers use auto commit, and `withAutoCommit()` overrides it for a single consumer.

#### Manual Commit
With manual commit, the handler decides when offsets are committed, by calling the `commit` or `commitAsync` methods of the consumer:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withManualCommit()
    ->withHandler(function ($message, $consumer) {
        processMessage($message);

        $consumer->commit($message);
    });
```

Let exceptions propagate from the handler. Catching the exception of a failed message without rethrowing it makes the consumer move on as if it was processed.

See the [manual commit guide](../advanced-usage/manual-commit.md) for the available commit methods and patterns.

### Stopping after a number of messages or seconds
If you want to consume a limited amount of messages, use the `stopAfterMessages` method, and to consume for a limited amount of time, use the `stopAfterSeconds` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->stopAfterMessages(100);

$consumer = \Junges\Kafka\Facades\Kafka::consumer()->stopAfterSeconds(3600);
```

To stop once there are no messages left, see [stopping the consumer when there are no messages left](../advanced-usage/stop-consumer-after-last-message.md).

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

### Configuration callbacks
librdkafka reports some information through callbacks, which you can register on the consumer builder:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->onError(function ($kafka, int $error, string $reason) {
        logger()->error("Kafka error: {$reason}");
    })
    ->onLog(function ($kafka, int $level, string $facility, string $message) {
        logger()->debug($message);
    })
    ->onStatistics(function ($kafka, string $json, int $length) {
        // Emitted every "statistics.interval.ms", when that option is set
    })
    ->onOffsetCommit(function ($kafka, int $error, array $partitions) {
        // Called with the result of every commit, including background and asynchronous ones
    });
```

The same methods are available on [connections](../advanced-usage/connections.md), where they apply to the producer and to every consumer of the connection. The `onRebalance` method sets the rebalance callback, see [partition discovery](partition-discovery.md) for simpler ways to react to partition assignments, and `onOAuthBearerTokenRefresh` is described in [SASL authentication](../advanced-usage/sasl-authentication.md).
