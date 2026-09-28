---
title: SASL Authentication
weight: 3
---

```+parse
<x-sponsors.request-sponsor/>
```

SASL allows your producers and your consumers to authenticate to your Kafka cluster, which verifies their identity.
It's also a secure way to enable your clients to endorse an identity.

SASL is configured per connection, in your `config/kafka.php` file. It is used by the producer and by every consumer of the connection when a username is set and the security protocol is `SASL_PLAINTEXT` or `SASL_SSL`:

```php
'connections' => [
    'default' => [
        'brokers' => env('KAFKA_BROKERS'),
        'security_protocol' => 'SASL_SSL',
        'sasl' => [
            'mechanism' => 'SCRAM-SHA-512',
            'username' => env('KAFKA_USERNAME'),
            'password' => env('KAFKA_PASSWORD'),
        ],
    ],
],
```

To use different credentials for a single consumer, you can use the `withSasl` method of the consumer builder. The mechanism and the security protocol accept the `Junges\Kafka\Config\SaslMechanism` and `Junges\Kafka\Config\SecurityProtocol` enums, or their string values. The security protocol is optional: by default, `SASL_SSL` is used when the connection is encrypted, using `SSL` or `SASL_SSL`, and `SASL_PLAINTEXT` otherwise, so a consumer never connects with less encryption than its connection:

```php
use Junges\Kafka\Config\SaslMechanism;
use Junges\Kafka\Config\SecurityProtocol;

$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withSasl(
        username: 'username',
        password: 'password',
        mechanism: SaslMechanism::SCRAM_SHA_512,
        securityProtocol: SecurityProtocol::SASL_SSL,
    );
```

The available mechanisms are `PLAIN`, `SCRAM_SHA_256`, `SCRAM_SHA_512`, `GSSAPI` and `OAUTHBEARER`.

### OAUTHBEARER Authentication

If your Kafka cluster requires OAuth 2.0 (OAUTHBEARER) authentication, which is common with Confluent Cloud, AWS MSK with IAM, or enterprise deployments, you can use the `onOAuthBearerTokenRefresh` method. This registers a callback that librdkafka invokes whenever it needs a fresh token.

The callback is usually registered on the connection, in the `boot` method of a service provider, so it is used by the producer and by every consumer of the connection. Set the `sasl.mechanisms` option to `OAUTHBEARER` in the connection options:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::connection()->onOAuthBearerTokenRefresh(function ($client, string $oauthConfig): void {
    $client->oauthbearerSetToken(fetchTokenFromIdP(), getTokenExpiryMs(), 'my-client-id');
});
```

You can also register it for a single consumer:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer(['my.topic'])
    ->withOptions([
        'security.protocol' => 'SASL_SSL',
        'sasl.mechanisms'   => 'OAUTHBEARER',
    ])
    ->onOAuthBearerTokenRefresh(function ($consumer, string $oauthConfig): void {
        $token      = fetchTokenFromIdP();
        $expiresMs  = getTokenExpiryMs($token);
        $principal   = 'my-client-id';
        $extensions = [
            'logicalCluster' => 'lkc-xxxxx',
            'identityPoolId' => 'pool-xxxxx',
        ];

        $consumer->oauthbearerSetToken($token, $expiresMs, $principal, $extensions);
    })
    ->withHandler(new MyMessageHandler())
    ->build()
    ->consume();
```

The callback receives two arguments: the `RdKafka\KafkaConsumer` (or `RdKafka\Producer`) instance and the `oauthbearer_config` string from your librdkafka configuration. Inside the callback, call `$consumer->oauthbearerSetToken()` to provide the token, or `$consumer->oauthbearerSetTokenFailure($reason)` if the token could not be obtained.

This method is available on both connections and the consumer builder.

### TLS Authentication

For using TLS authentication with Laravel Kafka you can configure your connection using the following options:

```php
'connections' => [
    'default' => [
        'brokers' => env('KAFKA_BROKERS'),
        'security_protocol' => 'SSL',
        'options' => [
            'ssl.ca.location' => '/some/location/kafka.crt',
            'ssl.certificate.location' => '/some/location/client.crt',
            'ssl.key.location' => '/some/location/client.key',
            'ssl.endpoint.identification.algorithm' => 'none',
        ],
    ],
],
```