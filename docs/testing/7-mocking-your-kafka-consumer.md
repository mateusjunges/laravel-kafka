---
title: Mocking your kafka consumer
weight: 7
---

If you want to test that your consumers are working correctly, you can mock and execute the consumer to
ensure that everything works as expected.

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
        new ConsumedMessage(
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
    $consumer = Kafka::createConsumer(['mark-post-as-published-topic'])
        ->withHandler(function (KafkaConsumerMessage $message) use (&$posts) {
            $post = Post::find($message->getBody()['post_id']);
    
            $post->update(['published_at' => now()->format("Y-m-d H:i:s")]);
    
            return 0;

        })->build();
        
    $consumer->consume();

    // Now, you can test if the post published_at field is not empty, or anything else you want to test:
    
    $this->assertNotNull($post->refresh()->published_at);
}
```

It also works for batch messages