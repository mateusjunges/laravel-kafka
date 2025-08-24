---
title: Consumer groups
weight: 4
---

Kafka consumers belonging to the same consumer group share a group id. The consumers in a group divides the topic partitions as fairly amongst themselves as possible by establishing that each partition is only consumed by a single consumer from the group.

```+parse
<x-sponsors.request-sponsor/>
```

To attach your consumer to a consumer group, you can use the method `withConsumerGroupId` to specify the consumer group id:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer()->withConsumerGroupId('foo');
```

### Kafka Consumer Group Rebalancing

Watch how Kafka automatically redistributes partitions among consumers when the consumer group changes. Add or remove consumers to see the rebalancing process in action.

```+parse
<x-docs.animation.rebalance />
```

### Partition Assignment Strategies

You can configure how Kafka assigns partitions to consumers in your consumer group by specifying a rebalance strategy:

```php
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Config\RebalanceStrategy;

$consumer = Kafka::consumer()
    ->withConsumerGroupId('my-group')
    ->withRebalanceStrategy(RebalanceStrategy::ROUND_ROBIN);
```

#### Available Strategies

- **Range** (`RebalanceStrategy::RANGE`): Default strategy. Assigns partitions on a per-topic basis by dividing partitions evenly among consumers.
- **Round Robin** (`RebalanceStrategy::ROUND_ROBIN`): Distributes partitions evenly across all consumers in a round-robin fashion.
- **Sticky** (`RebalanceStrategy::STICKY`): Maintains balanced assignments while preserving existing assignments during rebalancing.
- **Cooperative Sticky** (`RebalanceStrategy::COOPERATIVE_STICKY`): Same as sticky but allows cooperative rebalancing for reduced downtime.

#### Examples

```php
// Using Range strategy (default)
$consumer = Kafka::consumer()
    ->withConsumerGroupId('my-group')
    ->withRebalanceStrategy(RebalanceStrategy::RANGE);

// Using Round Robin for better distribution
$consumer = Kafka::consumer()
    ->withConsumerGroupId('my-group')
    ->withRebalanceStrategy(RebalanceStrategy::ROUND_ROBIN);

// Using Sticky for minimal disruption during rebalancing
$consumer = Kafka::consumer()
    ->withConsumerGroupId('my-group')
    ->withRebalanceStrategy(RebalanceStrategy::STICKY);

// Using Cooperative Sticky for minimal downtime
$consumer = Kafka::consumer()
    ->withConsumerGroupId('my-group')
    ->withRebalanceStrategy(RebalanceStrategy::COOPERATIVE_STICKY);
```

You can also pass strategy names as strings:

```php
$consumer = Kafka::consumer()
    ->withConsumerGroupId('my-group')
    ->withRebalanceStrategy('sticky');
```

Or set the strategy using raw options:

```php
$consumer = Kafka::consumer()
    ->withConsumerGroupId('my-group')
    ->withOption('partition.assignment.strategy', 'sticky');
```