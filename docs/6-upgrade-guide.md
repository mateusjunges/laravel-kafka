---
title: Upgrade guide
weight: 6
---

## Upgrade to v2.x from v1.9.x

### High impact changes
 - The `\Junges\Kafka\Contracts\CanProduceMessages` contract was renamed to `\Junges\Kafka\Contracts\MessageProducer`
 - The `\Junges\Kafka\Contracts\CanPublishMessagesToKafka` contract was renamed to `\Junges\Kafka\Contracts\MessagePublisher`
- The `\Junges\Kafka\Contracts\KafkaProducerMessage` contract was renamed to `\Junges\Kafka\Contracts\ProducerMessage`
- The `\Junges\Kafka\Contracts\CanConsumeMessages` was renamed to `\Junges\Kafka\Contracts\MessageConsumer`
- The `\Junges\Kafka\Contracts\KafkaConsumerMessage` was renamed to `\Junges\Kafka\Contracts\ConsumerMessage`
- The `\Junges\Kafka\Contracts\CanConsumeMessagesFromKafka` was renamed to `\Junges\Kafka\Contracts\ConsumeMessagesFromKafka`

#### The `withSasl` method signature was changed.

The `withSasl` method now accepts all `SASL` parameters instead of a `Sasl` object.

```php
public function withSasl(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT');
```

### Updating dependencies
**PHP 8.1 Required**

This package now requires PHP 8.1 or higher.

You can use tools such as [rector](https://github.com/rectorphp/rector) to upgrade your app to PHP 8.1.