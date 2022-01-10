# Laravel Kafka
![docs/laravel-kafka.png](docs/laravel-kafka.png)

[![Latest Version On Packagist](http://poser.pugx.org/mateusjunges/laravel-kafka/v)](https://packagist.org/packages/mateusjunges/laravel-kafka)
[![Total Downloads](http://poser.pugx.org/mateusjunges/laravel-kafka/downloads)](https://packagist.org/packages/mateusjunges/laravel-kafka)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![Continuous Integration](https://github.com/mateusjunges/laravel-kafka/actions/workflows/run-tests.yml/badge.svg)](https://github.com/mateusjunges/laravel-kafka/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/mateusjunges/laravel-kafka/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/mateusjunges/laravel-kafka/actions/workflows/php-cs-fixer.yml)
[![PHP Version Require](http://poser.pugx.org/mateusjunges/laravel-kafka/require/php)](https://packagist.org/packages/mateusjunges/laravel-kafka)

Do you use Kafka in your laravel projects? All packages I've seen until today, including some built by myself, does not provide a nice
syntax usage syntax or, if it does, the test process with these packages are very painful.

This package provides a nice way of producing and consuming kafka messages in your Laravel projects.

Follow these docs to install this package and start using kafka in your laravel projects.

- [1. Installation](#installation)
- [2. Usage](#usage)
- [3. Producing Kafka Messages](#producing-kafka-messages)
  - [3.1 ProducerBuilder configuration methods](#producerbuilder-configuration-methods)
    - [3.1.2 Using custom serializers](#using-custom-serializers)
    - [3.1.3 Using AVRO serializer](#using-avro-serializer)
    - [3.1.4 Configuring the Kafka message payload](#configuring-the-kafka-message-payload)
    - [3.1.5 Configuring the kafka message headers](#configuring-message-headers)
    - [3.1.6 Configure the message body](#configure-the-message-body)
    - [3.1.7 Using kafka keys](#using-kafka-keys)
  - [3.2 Sending the message to Kafka](#sending-the-message-to-kafka)
- [4. Consuming kafka messages](#consuming-kafka-messages)
  - [4.1 Subscribing to a topic](#subscribing-to-a-topic)
  - [4.2 Configuring consumer groups](#configuring-consumer-groups)
  - [4.3 Configuring message handlers](#configuring-message-handlers)
  - [4.4 Configuring max messages to be consumed](#configuring-max-messages-to-be-consumed)
  - [4.5 Stopping consumer](#stopping-consumer-using-pcntl)
  - [4.6 Configuring a dead letter queue](#configuring-a-dead-letter-queue)
  - [4.7 Using SASL](#using-sasl)
  - [4.8 Using middlewares](#using-middlewares)
  - [4.9 Using custom deserializers](#using-custom-deserializers)
  - [4.10 Using AVRO deserializer](#using-avro-deserializer)
  - [4.11 Using auto-commit](#using-auto-commit)
  - [4.12 Using custom committers](#using-custom-committers)
  - [4.13 Setting kafka consumer configuration options](#setting-kafka-configuration-options)
  - [4.14 Building the consumer](#building-the-consumer)
  - [4.15 Consuming the kafka message](#consuming-the-kafka-messages)
  - [4.16 Using the built in consumer command](#using-the-built-in-consumer-command)
- [5. Using custom serializers/deserializers](#using-custom-serializersdeserializers)
- [6. Using `Kafka::fake()`method](#using-kafkafake)
  - [6.1 `assertPublished` method](#assertpublished-method)
  - [6.2 `assertPublishedOn` method](#assertpublishedon-method)
  - [6.3 `assertNothingPublished` method](#assertnothingpublished-method)
  - [6.4 `assertPublishedTimes` method](#assertpublishedtimes-method)
  - [6.5 `assertPublishedOnTimes` method](#assertpublishedontimes-method)

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

Kafka::publishOn('topic');
```

You can also specify the broker where you want to publish the message:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic', 'broker');
```

This method returns a `Junges\Kafka\Producers\ProducerBuilder::class` instance, and you can configure your message.

The `ProducerBuilder` class contains a few methods to configure your kafka producer. The following lines describes these methods.

## ProducerBuilder configuration methods
The `withConfigOption` method sets a `\RdKafka\Conf::class` option. You can check all available options [here][rdkafka_config].
This methods set one config per call, and you can use `withConfigOptions` passing an array of config name and config value 
as argument. Here's an example:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic')
    ->withConfigOption('property-name', 'property-value')
    ->withConfigOptions([
        'property-name' => 'property-value'
    ]);
```

While you are developing your application, you can enable debug with the `withDebugEnabled` method.
To disable debug mode, you can use `->withDebugEnabled(false)`, or `withDebugDisabled` methods.

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic')
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
$producer = \Junges\Kafka\Facades\Kafka::publishOn('topic')->usingSerializer(new MyCustomSerializer());
```

### Using AVRO serializer
To use the AVRO serializer, add the AVRO serializer:

```php
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry\CachedRegistry;
use FlixTech\SchemaRegistryApi\Registry\BlockingRegistry;
use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Registry\Cache\AvroObjectCacheAdapter;
use GuzzleHttp\Client;

$cachedRegistry = new CachedRegistry(
    new BlockingRegistry(
        new PromisingRegistry(
            new Client(['base_uri' => 'kafka-schema-registry:9081'])
        )
    ),
    new AvroObjectCacheAdapter()
);

$registry = new AvroSchemaRegistry($cachedRegistry);
$recordSerializer = new RecordSerializer($cachedRegistry);

//if no version is defined, latest version will be used
//if no schema definition is defined, the appropriate version will be fetched form the registry
$registry->addBodySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('bodySchemaName' /*, int $version, AvroSchema $definition */)
);
$registry->addKeySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('keySchemaName' /*, int $version, AvroSchema $definition */)
);

$serializer = new \Junges\Kafka\Message\Serializers\AvroSerializer($registry, $recordSerializer /*, AvroEncoderInterface::ENCODE_BODY */);

$producer = \Junges\Kafka\Facades\Kafka::publishOn('topic')->usingSerializer($serializer);
```

### Configuring the Kafka message payload
In kafka, you can configure your payload with a message, message headers and message key. All these configurations are available 
within `ProducerBuilder` class.

### Configuring message headers
To configure the message headers, use the `withHeaders` method:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic')
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

Kafka::publishOn('topic')->withMessage($message);
```

The `withBodyKey` method sets only a key in your message.

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic')->withBodyKey('key', 'value');
```

### Using Kafka Keys
In Kafka, keys are used to determine the partition within a log to which a message get's appended to.
If you want to use a key in your message, you should use the `withKafkaKey` method:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic')->withKafkaKey('your-kafka-key');
```

## Sending the message to Kafka
After configuring all your message options, you must use the `send` method, to send the message to kafka.

```php
use Junges\Kafka\Facades\Kafka;

/** @var \Junges\Kafka\Producers\ProducerBuilder $producer */
$producer = Kafka::publishOn('topic')
    ->withConfigOptions(['key' => 'value'])
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

$consumer = Kafka::createConsumer();
```

This method also allows you to specify the `topics` it should consume, the `broker` and the consumer `group id`:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer(['topic-1', 'topic-2'], 'group-id', 'broker');
```


This method returns a `Junges\Kafka\Consumers\ConsumerBuilder::class` instance, and you can use it to configure your consumer.

## Subscribing to a topic
With a consumer created, you can subscribe to a kafka topic using the `subscribe` method:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer()->subscribe('topic');
```

Of course, you can subscribe to more than one topic at once, either using an array of topics or specifying one by one:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer()->subscribe('topic-1', 'topic-2', 'topic-n');

// Or, using array:
$consumer = Kafka::createConsumer()->subscribe([
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

$consumer = Kafka::createConsumer()->withConsumerGroupId('foo');
```

## Configuring message handlers

Now that you have created your kafka consumer, you must create a handler for the messages this consumer receives. By default, a consumer is any `callable`.
You can use an invokable class or a simple callback. Use the `withHandler` method to specify your handler:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer();

// Using callback:
$consumer->withHandler(function(\Junges\Kafka\Contracts\KafkaConsumerMessage $message) {
    // Handle your message here
});
```

Or, using a invokable class:

```php
class Handler
{
    public function __invoke(\Junges\Kafka\Contracts\KafkaConsumerMessage $message){
        // Handle your message here
    }
}

$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->withHandler(new Handler)
```

The `KafkaConsumerMessage` contract gives you some handy methods to get the message properties: 

- `getKey()`: Returns the Kafka Message Key
- `getTopicName()`: Returns the topic where the message was published
- `getPartition()`: Returns the kafka partition where the message was published 
- `getHeaders()`: Returns the kafka message headers
- `getBody()`: Returns the body of the message
- `getOffset()`: Returns the offset where the message was published

## Configuring max messages to be consumed
If you want to consume a limited amount of messages, you can use the `withMaxMessages` method to set the max number of messages to be consumed by a 
kafka consumer:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->withMaxMessages(2);
```

## Stopping consumer using pcntl

Stopping consumers is very useful if you want to ensure you don't kill a process halfway through processing a consumed message.

To stop the consumer gracefully call the `stopConsume` method on a consumer instance.

This is particularly useful when using signal handlers. **NOTE** You will require the [Process Control Extension](https://www.php.net/manual/en/book.pcntl.php) to be installed to utilise the pcntl methods.

```php
function gracefulShutdown(Consumer $consumer) {
    $consumer->stopConsume(function() {
        echo 'Stopped consuming';
        exit(0);
    });
}

$consumer = Kafka::createConsumer(['topic'])
    ->withConsumerGroupId('group')
    ->withHandler(new Handler)
    ->build();
    
pcntl_signal(SIGINT, fn() => gracefulShutdown($consumer));

$consumer->consume();
```

## Configuring a dead letter queue
In kafka, a Dead Letter Queue (or DLQ), is a simple kafka topic in the kafka cluster which acts as the destination for messages that were not
able to make it to the desired destination due to some error.

To create a `dlq` in this package, you can use the `withDlq` method. If you don't specify the DLQ topic name, it will be created based on the topic you are consuming, 
adding the `-dlq` suffix to the topic name.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->withDlq();

//Or, specifying the dlq topic name:
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->withDlq('your-dlq-topic-name')
```

## Using SASL
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

## Using middlewares
Middlewares provides a convenient way to filter and inspecting your Kafka messages. To write a middleware in this package, you can 
use the `withMiddleware` method. The middleware is a callable in which the first argument is the message itself and the second one is
the next handler. The middlewares get executed in the order they are defined,

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->withMiddleware(function($message, callable $next) {
        // Perform some work here
        return $next($message);
    });
```

## Using custom deserializers
To set the deserializer you want to use, use the `usingDeserializer` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->usingDeserializer(new MyCustomDeserializer());
```

>NOTE: The deserializer class must use the same algorithm as the serializer used to produce this message.

## Using AVRO deserializer
To use the AVRO deserializer on your consumer, add the Avro deserializer:
```php
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry\CachedRegistry;
use FlixTech\SchemaRegistryApi\Registry\BlockingRegistry;
use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Registry\Cache\AvroObjectCacheAdapter;
use GuzzleHttp\Client;


$cachedRegistry = new CachedRegistry(
    new BlockingRegistry(
        new PromisingRegistry(
            new Client(['base_uri' => 'kafka-schema-registry:9081'])
        )
    ),
    new AvroObjectCacheAdapter()
);

$registry = new \Junges\Kafka\Message\Registry\AvroSchemaRegistry($cachedRegistry);
$recordSerializer = new RecordSerializer($cachedRegistry);

//if no version is defined, latest version will be used
//if no schema definition is defined, the appropriate version will be fetched form the registry
$registry->addBodySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('bodySchema' , 9 /* , AvroSchema $definition */)
);
$registry->addKeySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('keySchema' , 9 /* , AvroSchema $definition */)
);

// if you are only decoding key or value, you can pass that mode as additional third argument
// per default both key and body will get decoded
$deserializer = new \Junges\Kafka\Message\Deserializers\AvroDeserializer($registry, $recordSerializer /*, AvroDecoderInterface::DECODE_BODY */);

$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->usingDeserializer($deserializer);
```

## Using auto commit
The auto-commit check is called in every poll and it checks that the time elapsed is greater than the configured time. To enable auto commit, 
use the `withAutoCommit` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->withAutoCommit();
```

<a name="using-custom-committers"></a>
## Using custom committers
By default the committers provided by the `DefaultCommitterFactory` are provided.

To set a custom committer on your consumer, add the committer via a factory that implements the `CommitterFactory` interface:

```php
use \RdKafka\KafkaConsumer;
use \RdKafka\Message;
use \Junges\Kafka\Commit\Contracts\Committer;
use \Junges\Kafka\Commit\Contracts\CommitterFactory;
use \Junges\Kafka\Config\Config;

class MyCommitter implements Committer
{
    public function commitMessage(Message $message, bool $success) : void {
        // ...
    }
    
    public function commitDlq(Message $message) : void {
        // ...
    }  
}

class MyCommitterFactory implements CommitterFactory
{
    public function make(KafkaConsumer $kafkaConsumer, Config $config) : Committer {
        // ...
    }
}

$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->usingCommitterFactory(new MyCommitterFactory())
    ->build();
```

## Setting Kafka configuration options
To set configuration options, you can use two methods: `withOptions`, passing an array of option and option value or, using the `withOption method and
passing two arguments, the option name and the option value.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->withOptions([
        'option-name' => 'option-value'
    ]);
// Or:
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->withOption('option-name', 'option-value');
```

## Building the consumer
When you have finished configuring your consumer, you must call the `build` method, which returns a `Junges\Kafka\Consumers\Consumer` instance.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    // Configure your consumer here
    ->build();
```

## Consuming the kafka messages
After building the consumer, you must call the `consume` method to consume the messages:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->build();

$consumer->consume();
```

## Using the built in consumer command
This library provides you a built in consumer command, which you can use to consume messages.

To use this command, you must create a `Consumer` class, which extends `Junges\Kafka\Contracts\Consumer`.

Then, just use the following command:

```bash
php artisan kafka:consume --consumer=\\App\\Path\\To\\Your\\Consumer --topics=topic-to-consume
```

# Using custom serializers/deserializers
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
$producer = \Junges\Kafka\Facades\Kafka::publishOn('topic')->usingSerializer(new MyCustomSerializer());

$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->usingDeserializer(new MyCustomDeserializer());
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
         
         $producer = Kafka::publishOn('topic')
             ->withHeaders(['key' => 'value'])
             ->withBodyKey('foo', 'bar');
             
         $producer->send();
             
         Kafka::assertPublished($producer->getMessage());       
     }
}
```

You can also use `assertPublished` without passing the message argument:

```php
use Junges\Kafka\Facades\Kafka;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
     public function testMyAwesomeApp()
     {
         Kafka::fake();
         
         Kafka::publishOn('topic')
             ->withHeaders(['key' => 'value'])
             ->withBodyKey('foo', 'bar');
             
             
         Kafka::assertPublished();       
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
        
        $producer = Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');
            
        $producer->send();
        
        Kafka::assertPublishedOn('some-kafka-topic', $producer->getMessage());
        
        // Or:
        Kafka::assertPublishedOn('some-kafka-topic');
        
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
        
        $producer = Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');
            
        $producer->send();
        
        Kafka::assertPublishedOn('some-kafka-topic', $producer->getMessage(), function(Message $message) {
            return $message->getHeaders()['key'] === 'value';
        });
        
        // Or:
        Kafka::assertPublishedOn('some-kafka-topic', null, function(Message $message) {
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
            $producer = Kafka::publishOn('some-kafka-topic')
                ->withHeaders(['key' => 'value'])
                ->withBodyKey('key', 'value');
                
            $producer->send();
        }
        
        Kafka::assertNothingPublished();
    }
} 
```

## `assertPublishedTimes` method
Sometimes, you need to assert that Kafka has published a given number of messages. For that, you can use the `assertPublishedTimes` method:

```php
use PHPUnit\Framework\TestCase;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();

        Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');

        Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');

        Kafka::assertPublishedTimes(2);
    }
} 
```

## `assertPublishedOnTimes` method
To assert that messages were published on a given topic a given number of times, you can use the `assertPublishedOnTimes` method:
```php
use PHPUnit\Framework\TestCase;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();

        Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');

        Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');

        Kafka::assertPublishedOnTimes('some-kafka-topic', 2);
    }
} 
```


# Testing
Run `composer test` to test this package.

# Contributing
Thank you for considering contributing for the Laravel Kafka package! The contribution guide can be found [here][contributing].

# Credits
- [Mateus Junges](https://twitter.com/mateusjungess)
- [Arquivei](https://github.com/arquivei)

# License
The Laravel Kafka package is open-sourced software licenced under the [MIT][mit] License. Please see the [License File][license] for more information.

[rdkafka_config]:https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md
[contributing]: .github/CONTRIBUTING.md
[license]: LICENSE
[mit]: https://opensource.org/licenses/MIT
