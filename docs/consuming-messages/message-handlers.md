---
title: Message handlers
weight: 5
---

Now that you have created your kafka consumer, you must create a handler for the messages this consumer receives. By default, a consumer is any `callable`.
You can use an invokable class or a simple callback. Use the `withHandler` method to specify your handler:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer();

// Using callback:
$consumer->withHandler(function(\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
    // Handle your message here
});
```

Or, using an invokable class:

```php
class Handler
{
    public function __invoke(\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
        // Handle your message here
    }
}

$consumer = \Junges\Kafka\Facades\Kafka::consumer()->withHandler(new Handler)
```

The `ConsumerMessage` contract gives you some handy methods to get the message properties:

- `getKey()`: Returns the Kafka Message Key
- `getTopicName()`: Returns the topic where the message was published
- `getPartition()`: Returns the kafka partition where the message was published
- `getHeaders()`: Returns the kafka message headers
- `getBody()`: Returns the body of the message
- `getOffset()`: Returns the offset where the message was published

## Manual Commit in Handlers

When using manual commit mode (`withAutoCommit(false)`), your handlers receive a `$consumer` parameter that provides commit methods. This allows you to control exactly when message offsets are committed:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withManualCommit()  // Enable manual commit mode
    ->withHandler(function(\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
        try {
            // Process your message
            $data = json_decode($message->getBody(), true);
            processBusinessLogic($data);
            
            // Commit the message after successful processing
            $consumer->commit($message);
            
        } catch (ValidationException $e) {
            // Don't commit invalid messages, send to DLQ or handle differently
            Log::warning('Invalid message format', ['message' => $message->getBody()]);
            
        } catch (Exception $e) {
            Log::error('Processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    });
```

### Available Commit Methods

The `$consumer` parameter provides these commit methods:

**Synchronous commits** (blocking):
- `$consumer->commit()` - Commit current assignment offsets
- `$consumer->commit($message)` - Commit specific message offset

**Asynchronous commits** (non-blocking, better performance):
- `$consumer->commitAsync()` - Commit current assignment offsets
- `$consumer->commitAsync($message)` - Commit specific message offset


## Handler Classes

You can also create dedicated handler classes by implementing the `Handler` interface. Handler classes receive both the message and consumer parameters, just like closure handlers:

```php
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

class ProcessOrderHandler implements Handler
{
    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        try {
            $order = json_decode($message->getBody(), true);
            
            // Process the order
            $this->processOrder($order);
            
            // Manual commit after successful processing
            $consumer->commit($message);
            
        } catch (ValidationException $e) {
            // Don't commit invalid messages
            Log::warning('Invalid order data', ['message' => $message->getBody()]);
            
        } catch (Exception $e) {
            // Don't commit on processing errors
            Log::error('Order processing failed', ['error' => $e->getMessage()]);
            throw $e; // Re-throw to trigger DLQ handling if configured
        }
    }
    
    private function processOrder(array $order): void
    {
        // Your business logic here
    }
}
```

**Using Handler classes with the consumer:**

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer(['orders'])
    ->withManualCommit()  // Enable manual commit mode
    ->withHandler(new ProcessOrderHandler())
    ->build();

$consumer->consume();
```

```+parse
<x-sponsors.request-sponsor/>
```

