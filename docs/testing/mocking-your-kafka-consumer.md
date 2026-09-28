---
title: Mocking your kafka consumer
weight: 7
---

If you want to test that your consumers are working correctly, you can mock and execute the consumer to
ensure that everything works as expected.

```+parse
<x-sponsors.request-sponsor/>
```


You just need to tell kafka which messages the consumer should receive and then start your consumer. This package will
run all the specified messages through the consumer and stop after the last message, so you can perform whatever
assertions you want to.

For example, let's say you want to test that a simple blog post was published after consuming a `post-published` message:

```php
public function test_post_is_marked_as_published()
{
    // First, you use the fake method:
    \Junges\Kafka\Facades\Kafka::fake();
    
    // Then, tells Kafka what messages the consumer should receive:
    \Junges\Kafka\Facades\Kafka::shouldReceiveMessages([
        new \Junges\Kafka\Message\ConsumedMessage(
            topicName: 'mark-post-as-published-topic',
            partition: 0,
            headers: [],
            body: ['post_id' => 1],
            key: null,
            offset: 0,
            timestamp: 0
        ),
    ]);
    
    // Now, instantiate your consumer and start consuming messages. It will consume only the messages
    // specified in `shouldReceiveMessages` method:
    $consumer = \Junges\Kafka\Facades\Kafka::consumer(['mark-post-as-published-topic'])
        ->withHandler(function (\Junges\Kafka\Contracts\ConsumerMessage $message) use (&$posts) {
            $post = Post::find($message->getBody()['post_id']);
    
            $post->update(['published_at' => now()->format("Y-m-d H:i:s")]);
    
            return 0;

        })->build();
        
    $consumer->consume();

    // Now, you can test if the post published_at field is not empty, or anything else you want to test:
    
    $this->assertNotNull($post->refresh()->published_at);
}
```

### Failed messages

Faked consumers handle failed messages like real ones: they are [retried](../consuming-messages/handling-failed-messages.md), with the attempt number available through `getAttempts()`, the failure callback is called, and the message is then sent to the dead letter queue, skipped, or stops the consumer with a `Junges\Kafka\Exceptions\ConsumerException`. Instead of publishing messages to the dead letter queue, faked consumers only dispatch the `MessageSentToDLQ` event, so you can assert that a message was sent to it:

```php
use Illuminate\Support\Facades\Event;
use Junges\Kafka\Events\MessageSentToDLQ;

Event::fake([MessageSentToDLQ::class]);

Kafka::consumerFor(OrdersConsumer::class)->build()->consume();

Event::assertDispatched(MessageSentToDLQ::class);
```

### Testing consumer classes

[Consumer classes](../consuming-messages/class-structure.md) can be tested the same way, building them with the `consumerFor` method:

```php
use App\Kafka\Consumers\OrdersConsumer;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;

public function test_orders_are_created()
{
    Kafka::fake();

    Kafka::shouldReceiveMessages([
        new ConsumedMessage(
            topicName: 'orders',
            partition: 0,
            headers: [],
            body: ['id' => 1],
            key: null,
            offset: 0,
            timestamp: 0,
        ),
    ]);

    Kafka::consumerFor(OrdersConsumer::class)->build()->consume();

    $this->assertDatabaseHas('orders', ['id' => 1]);
}
```
