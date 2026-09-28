---
title: Configuring consumer options
weight: 6
---

The `ConsumerBuilder` offers you some few configuration options.

```+parse
<x-sponsors.request-sponsor/>
```

### Configuring a dead letter queue
In kafka, a Dead Letter Queue (or DLQ), is a simple kafka topic in the kafka cluster which acts as the destination for messages that were not
able to make it to the desired destination due to some error.

To create a `dlq` in this package, you can use the `withDlq` method. If you don't specify the DLQ topic name, it will be created based on the topic you are consuming,
adding the `-dlq` suffix to the topic name.

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
The package supports two commit modes for controlling when message offsets are committed to Kafka:

#### Auto Commit (Default)
With auto-commit enabled, messages are automatically committed after your handler successfully processes them. This is the default behavior and simplest to use:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withAutoCommit() // Optional as this is the default
    ->withHandler(function($message, $consumer) {
        // Process your message.
        // Message is automatically committed after handler returns successfully
    });
```

#### Manual Commit
With manual commit, you have full control over when messages are committed. This provides better error handling and processing guarantees:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withManualCommit()
    ->withHandler(function($message, $consumer) {
        try {
            // Process your message
            processMessage($message);
            
            // Manually commit the message
            $consumer->commit($message);  // Synchronous commit
            // OR: $consumer->commitAsync($message);  // Asynchronous commit
            
        } catch (Exception $e) {
            Log::error('Message processing failed', ['error' => $e->getMessage()]);
        }
    });
```

#### When to use each mode:
- **Auto-commit**: Simple use cases where message loss is acceptable, and you want automatic offset management
- **Manual commit**: When you need guaranteed processing, complex error handling, or want to implement custom commit strategies

#### Available commit methods:
When using manual commit mode, your handlers can use these methods on the `$consumer` parameter:

- `commit()` - Commit current assignment offsets (synchronous)
- `commit($message)` - Commit specific message offset (synchronous)
- `commitAsync()` - Commit current assignment offsets (asynchronous)
- `commitAsync($message)` - Commit specific message offset (asynchronous)

For more detailed information about manual commit patterns, see the [Manual Commit guide](../advanced-usage/manual-commit.md).

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