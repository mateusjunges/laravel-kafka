---
title: SASL Authentication
weight: 3
---

SASL allows your producers and your consumers to authenticate to your Kafka cluster, which verifies their identity.
It's also a secure way to enable your clients to endorse an identity. To provide SASL configuration, you can use the `withSasl` method,
passing a `Junges\Kafka\Config\Sasl` instance as the argument:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->withSasl(new \Junges\Kafka\Config\Sasl(
        password: 'password',
        username: 'username'
        mechanisms: 'authentication mechanism'
    ));
```

You can also set the security protocol used with sasl. It's optional and by default `SASL_PLAINTEXT` is used, but you can set it to `SASL_SSL`:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->withSasl(new \Junges\Kafka\Config\Sasl(
        password: 'password',
        username: 'username'
        mechanisms: 'authentication mechanism',
        securityProtocol: 'SASL_SSL',
    ));
```
