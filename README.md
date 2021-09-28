# Laravel Kafka
![docs/laravel-kafka.png](docs/laravel-kafka.png)

[![Latest Version On Packagist](http://poser.pugx.org/mateusjunges/laravel-kafka/v)](https://packagist.org/packages/mateusjunges/laravel-kafka)
[![Total Downloads](http://poser.pugx.org/mateusjunges/laravel-kafka/downloads)](https://packagist.org/packages/mateusjunges/laravel-kafka)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![Continuous Integration](https://github.com/mateusjunges/laravel-kafka/actions/workflows/run-tests.yml/badge.svg)](https://github.com/mateusjunges/laravel-kafka/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/mateusjunges/laravel-kafka/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/mateusjunges/laravel-kafka/actions/workflows/php-cs-fixer.yml)
[![PHP Version Require](http://poser.pugx.org/mateusjunges/laravel-kafka/require/php)](https://packagist.org/packages/mateusjunges/laravel-kafka)

Do you use Kafka in your laravel packages? All packages I've seen until today, including some built by myself, does not provide a nice
syntax usage syntax or, if it does, the test process with these packages are very painful.

This package provides a nice way of producing and consuming kafka messages in your Laravel projects.

Follow these docs to install this package and start using kafka with ease.

- [1. Installation](#installation)
- [2. Usage](#usage)
- [3. Producing Kafka Messages](#producing-kafka-messages)
  - [3.1 ProducerBuilder configuration methods](#producerbuilder-configuration-methods)
    - [3.1.2 Using custom serializers](#using-custom-serializers)
    - [3.1.3 Configuring the Kafka message payload](#configuring-the-kafka-message-payload)
    - [3.1.4 Configuring the kafka message headers](#configuring-message-headers)
    - [3.1.5 Configure the message body](#configure-the-message-body)
    - [3.1.6 Using kafka keys](#using-kafka-keys)
  - [3.2 Sending the message to Kafka](#sending-the-message-to-kafka)
- [4. Consuming kafka messages](#consuming-kafka-messages)
  - [4.1 Subscribing to a topic](#subscribing-to-a-topic)
  - [4.2 Configuring consumer groups](#configuring-consumer-groups)
  - [4.3 Configuring message handlers](#configuring-message-handlers)
  - [4.4 Configuring max messages to be consumed](#configuring-max-messages-to-be-consumed)
  - [4.5 Configuring a dead letter queue](#configuring-a-dead-letter-queue)
  - [4.6 Using SASL](#using-sasl)
  - [4.7 Using middlewares](#using-middlewares)
  - [4.8 Using custom deserializers](#using-custom-deserializers)
  - [4.9 Using auto-commit](#using-auto-commit)
  - [4.10 Setting kafka consumer configuration options](#setting-kafka-configuration-options)
  - [4.11 Building the consumer](#building-the-consumer)
  - [4.12 Consuming the kafka message](#consuming-the-kafka-messages)
- [5. Using custom encoders and decoders]()
- [6. Using `Kafka::fake()`method](#using-kafkafake)
  - [6.1 `assertPublished` method](#assertpublished-method)
  - [6.2 `assertPublishedOn` method](#assertpublishedon-method)
  - [6.3 `assertNothingPublished` method](#assertnothingpublished-method)

# Installation
To install this package, you must have installed PHP RdKafka extension. You can follow the steps [here](https://github.com/edenhill/librdkafka#installation)
to install rdkafka in your system.

With RdKafka installed, require this package with composer:

```bash
composer require mateusjunges/laravel-kafka
```

You can publish the package configuration using:
```text
php artisan vendor:publish --tag=laravel-kafka-config
```
Now you are good to go!


# Usage
After installing the package, you can start producing and consuming Kafka messages.

# Producing Kafka Messages
To publish your messages to Kafka, you can use the `publishOn` method, of `Junges\Kafka\Facades\Kafka` class:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('broker', 'topic');
```

This method returns a `Junges\Kafka\Producers\ProducerBuilder::class` instance, and you can configure your message.

The `ProducerBuilder` class contains a few methods to configure your kafka producer. The following lines describes these methods.

## ProducerBuilder configuration methods
The `withConfigOption` method sets a `\RdKafka\Conf::class` option. You can check all available options [here][rdkafka_config].
This methods set one config per call, and you can use `withConfigOptions` passing an array of config name and config value 
as argument. Here's an example:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('broker', 'topic')
    ->withConfigOption('property-name', 'property-value')
    ->withConfigOptions([
        'property-name' => 'property-value'
    ]);
```

While you are developing your application, you can enable debug with the `withDebugEnabled` method.
To disable debug mode, you can use `->withDebugEnabled(false)`, or `withDebugDisabled` methods.

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('broker', 'topic')
    ->withConfigOption('property-name', 'property-value')
    ->withConfigOptions([
        'property-name' => 'property-value'
    ])
    ->withDebugEnabled() // To enable debug mode
    ->withDebugDisabled() // To disable debug mode
    ->withDebugEnabled(false) // Also to disable debug mode
```

### Using custom serializers
To use custom serializers, you must use the `usingSerializer` method:
```php
$producer = \Junges\Kafka\Facades\Kafka::publishOn('broker')->usingSerializer(new MyCustomSerializer());
```

### Configuring the Kafka message payload
In kafka, you can configure your payload with a message, message headers and message key. All these configurations are available 
within `ProducerBuilder` class.

### Configuring message headers
To configure the message headers, use the `withHeaders` method:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('broker', 'topic')
    ->withHeaders([
        'header-key' => 'header-value'
    ])
```

### Configure the message body
You can configure the message with the `withMessage` or `withBodyKey` methods. 

The `withMessage` sets the entire message, and it accepts a `Junges\Kafka\Message\Message::class` instance as argument.

This is how you should use it:

```php
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

$message = new Message(
    headers: ['header-key' => 'header-value'],
    body: ['key' => 'value'],
    key: 'kafka key here'  
)

Kafka::publishOn('broker', 'topic')->withMessage($message);
```

The `withBodyKey` method sets only a key in your message.

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('broker', 'topic')->withBodyKey('key', 'value');
```

### Using Kafka Keys
In Kafka, keys are used to determine the partition within a log to which a message get's appended to.
If you want to use a key in your message, you should use the `withKafkaKey` method:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('broker', 'topic')->withKafkaKey('your-kafka-key');
```

## Sending the message to Kafka
After configuring all your message options, you must use the `send` method, to send the message to kafka.

```php
use Junges\Kafka\Facades\Kafka;

/** @var \Junges\Kafka\Producers\ProducerBuilder $producer */
$producer = Kafka::publishOn('broker', 'topic')
    ->withConfigOptions(['key' => 'value'])
    ->withKafkaKey('your-kafka-key')
    ->withKafkaKey('kafka-key')
    ->withHeaders(['header-key' => 'header-value']);

$producer->send();
```
# Consuming Kafka Messages
If your application needs to read messages from a Kafka topic, you must create a consumer object, subscribe to the appropriate topic
and start receiving messages. 

To create a consumer using this package, you can use the `createConsumer` method, on Kafka facade:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer('broker');
```

This method returns a `Junges\Kafka\Consumers\ConsumerBuilder::class` instance, and you can use it to configure your consumer.

## Subscribing to a topic
With a consumer created, you can subscribe to a kafka topic using the `subscribe` method:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer('broker')->subscribe('topic');
```

Of course, you can subscribe to more than one topic at once, either using an array of topics or specifying one by one:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer('broker')->subscribe('topic-1', 'topic-2', 'topic-n');

// Or, using array:
$consumer = Kafka::createConsumer('broker')->subscribe([
    'topic-1',
    'topic-2',
    'topic-n'
]);
```

## Configuring consumer groups
Kafka consumers belonging to the same consumer group share a group id. THe consumers in a group divides the topic partitions as fairly amongst themselves as possible by
establishing that each partition is only consumed by a single consumer from the group.

To attach your consumer to a consumer group, you can use the method `withConsumerGroupId` to specify the consumer group id:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer('broker')->withConsumerGroupId('foo');
```

## Configuring message handlers

Now that you have created your kafka consumer, you must create a handler for the messages this consumer receives. By default, a consumer is any `callable`.
You can use an invokable class or a simple callback. Use the `withHandler` method to specify your handler:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker');

// Using callback:
$consumer->withHandler(function(\RdKafka\Message $message) {
    // Handle your message here
});
```

Or, using a invokable class:

```php
class Handler
{
    public function __invoke(\RdKafka\Message $message){
        // Handle your message here
    }
}

$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')->withHandler(Handler::class)
```

## Configuring max messages to be consumed
If you want to consume a limited amount of messages, you can use the `withMaxMessages` method to set the max number of messages to be consumed by a 
kafka consumer:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')->withMaxMessages(2);
```

## Configuring a dead letter queue
In kafka, a Dead Letter Queue (or DLQ), is a simple kafka topic in the kafka cluster which acts as the destination for messages that were not
able to make it to the desired destination due to some error.

To create a `dlq` in this package, you can use the `withDlq` method. If you don't specify the DLQ topic name, it will be created based on the topic you are consuming, 
adding the `-dlq` suffix to the topic name.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')->withDlq();

//Or, specifying the dlq topic name:
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')->withDlq('your-dlq-topic-name')
```

## Using SASL
SASL allows your producers and your consumers to authenticate to your Kafka cluster, which verifies their identity. 
It's also a secure way to enable your clients to endorse an identity. To provide SASL configuration, you can use the `withSasl` method,
passing a `Junges\Kafka\Config\Sasl` instance as the argument:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')
    ->withSasl(new \Junges\Kafka\Config\Sasl(
        password: 'password',
        username: 'username'
        mechanisms: 'authentication mechanism'
    ));
```

## Using middlewares
Middlewares provides a convenient way to filter and inspecting your Kafka messages. To write a middleware in this package, you can 
use the `withMiddleware` method. The middleware is a callable in which the first argument is the message itself and the second one is
the next handler. The middlewares get executed in the order they are defined,

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')
    ->withMiddleware(function($message, callable $next) {
        // Perform some work here
        return $next($message);
    });
```

## Using custom deserializers
To set the deserializer you want to use, use the `usingDeserializer` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')->usingDeserializer(new MyCustomDeserializer());
```

>NOTE: The deserializer class must use the same algorithm as the serializer used to produce this message.

## Using auto commit
The auto-commit check is called in every poll and it checks that the time elapsed is greater than the configured time. To enable auto commit, 
use the `withAutoCommit` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')->withAutoCommit();
```

## Setting Kafka configuration options
To set configuration options, you can use two methods: `withOptions`, passing an array of option and option value or, using the `withOption method and
passing two arguments, the option name and the option value.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')
    ->withOptions([
        'option-name' => 'option-value'
    ]);
// Or:
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')
    ->withOption('option-name', 'option-value');
```

## Building the consumer
When you have finished configuring your consumer, you must call the `build` method, which returns a `Junges\Kafka\Consumers\Consumer` instance.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')
    // Configure your consumer here
    ->build();
```

## Consuming the kafka messages
After building the consumer, you must call the `consume` method to consume the messages:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->build();

$consumer->consume();
```

# Using custom encoders/decoders
Serialization is the process of converting messages to bytes. Deserialization is the inverse process - converting a stream of bytes into and object. In a nutshell,
it transforms the content into readable and interpretable information.
Basically, in order to prepare the message for transmission from the producer we use serializers. This package supports three serializers out of the box:
- NullSerializer / NullDeserializer
- JsonSerializer / JsonDeserializer
- AvroSerializer / JsonDeserializer

## Changing default serializers and deserializers
The default Serializer is resolved using the `MessageSerializer` and `MessageDeserializer` contracts. Out of the box, the `Json` serializers are used.

To set the default serializer, you can bind the `MessageSerializer` and `MessageDeserializer` contracts to any class which implements this interfaces.

Open your `AppServiceProvider` class and add this lines to the `register` method: 

```php
$this->app->bind(\Junges\Kafka\Contracts\MessageSerializer::class, function () {
   return new MyCustomSerializer();
});

$this->app->bind(\Junges\Kafka\Contracts\MessageDeserializer::class, function() {
    return new MyCustomDeserializer();
});
```

## Creating a custom serializer
To create a custom serializer, you need to create a class that implements the `\Junges\Kafka\Contracts\MessageSerializer` contract.
This interface force you to declare the `serialize` method.

## Creating a custom deserializer
To create a custom deserializer, you need to create a class that implements the `\Junges\Kafka\Contracts\MessageDeserializer` contract.
This interface force you to declare the `deserialize` method.

## Replacing serializers and deserializers on the fly
Serializers and deserializers need to be set both on the Producer and the Consumer classes.
To set the producer serializer, you must use the `usingSerializer` method, available on the `ProducerBuilder` class.
To set the consumer deserializer, you must use the `usingDeserializer` method, available on the `ConsumerBuilder` class.

```php
$producer = \Junges\Kafka\Facades\Kafka::publishOn('broker')->usingSerializer(new MyCustomSerializer());

$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('broker')->usingDeserializer(new MyCustomDeserializer());
```

# Using `Kafka::fake()`
When testing your application, you may wish to "mock" certain aspects of the app, so they are not actually executed during a given test. 
This package provides convenient helpers for mocking the kafka producer out of the box. These helpers primarily provide a convenience layer over Mockery
so you don't have to manually make complicated Mockery method calls.

The Kafka facade also provides methods to perform assertions over published messages, such as `assertPublished`, `assertPublishedOn` and `assertNothingPublished`.

## `assertPublished` method
```php
use Junges\Kafka\Facades\Kafka;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
     public function testMyAwesomeApp()
     {
         Kafka::fake();
         
         $producer = Kafka::publishOn('broker', 'topic')
             ->withHeaders(['key' => 'value'])
             ->withBodyKey('foo', 'bar');
             
         $producer->send();
             
         Kafka::assertPublished($producer->getMessage());       
     }
}
```

## `assertPublishedOn` method
If you want to assert that a message was published in a specific kafka topic, you can use the `assertPublishedOn` method:

```php
use PHPUnit\Framework\TestCase;
use Junges\Kafka\Facades\Kafka;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();
        
        $producer = Kafka::publishOn('broker', 'some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');
            
        $producer->send();
        
        Kafka::assertPublishedOn('some-kafka-topic', $producer->getMessage());
    }
}
```

You can also use a callback function to perform assertions within the message using a callback in which the argument is the published message
itself.

```php
use PHPUnit\Framework\TestCase;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();
        
        $producer = Kafka::publishOn('broker', 'some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');
            
        $producer->send();
        
        Kafka::assertPublishedOn('some-kafka-topic', $producer->getMessage(), function(Message $message) {
            return $message->getHeaders()['key'] === 'value';
        });
    }
} 
```
## `assertNothingPublished` method
You can also assert that nothing was published at all, using the `assertNothingPublished`:

```php
use PHPUnit\Framework\TestCase;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();
        
        if (false) {
            $producer = Kafka::publishOn('broker', 'some-kafka-topic')
                ->withHeaders(['key' => 'value'])
                ->withBodyKey('key', 'value');
                
            $producer->send();
        }
        
        Kafka::assertNothingPublished();
    }
} 
```


# Testing
Run `composer test` to test this package.

# Contributing
Thank you for considering contributing for the Laravel ACL package! The contribution guide can be found [here][contributing].

# Credits
- [Mateus Junges](https://twitter.com/mateusjungess)
- [Arquivei](https://github.com/arquivei)

# License
The Laravel Kafka package is open-sourced software licenced under the [MIT][mit] License. Please see the [License File][license] for more information.

[rdkafka_config]:https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md
[contributing]: .github/CONTRIBUTING.md
[license]: LICENSE
[mit]: https://opensource.org/licenses/MIT
