---
title: SASL Authentication
weight: 3
---

```+parse
<x-sponsors.request-sponsor/>
```

SASL allows your producers and your consumers to authenticate to your Kafka cluster, which verifies their identity.
It's also a secure way to enable your clients to endorse an identity. To provide SASL configuration, you can use the `withSasl` method,
passing a `Junges\Kafka\Config\Sasl` instance as the argument:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withSasl(
        password: 'password',
        username: 'username',
        mechanisms: 'authentication mechanism'
    );
```

You can also set the security protocol used with sasl. It's optional and by default `SASL_PLAINTEXT` is used, but you can set it to `SASL_SSL`:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withSasl(
        password: 'password',
        username: 'username',
        mechanisms: 'authentication mechanism',
        securityProtocol: 'SASL_SSL',
    );
```

```+parse
<x-docs.tip title="Hot tip!">
    When using the `withSasl` method, the securityProtocol set in this method takes priority over `withSecurityProtocol` method.
</x-docs.tip>
```

### TLS Authentication

For using TLS authentication with Laravel Kafka you can configure your client using the following options:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->withOptions([
        'ssl.ca.location' => '/some/location/kafka.crt',
        'ssl.certificate.location' => '/some/location/client.crt',
        'ssl.key.location' => '/some/location/client.key',
        'ssl.endpoint.identification.algorithm' => 'none'
    ]);
```