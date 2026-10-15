<?php declare(strict_types=1);

namespace Junges\Kafka\Console\Commands\Concerns;

use Junges\Kafka\KafkaConsumer;

/** @mixin \Illuminate\Console\Command */
trait ResolvesConsumerClasses
{
    /**
     * Resolve a consumer class, either from its fully qualified name or its name in the App\Kafka\Consumers namespace.
     *
     * @return class-string<KafkaConsumer>|null
     */
    protected function resolveConsumerClass(string $consumer): ?string
    {
        $candidates = [$consumer, $this->laravel->getNamespace().'Kafka\\Consumers\\'.$consumer];

        foreach ($candidates as $class) {
            if (class_exists($class) && is_subclass_of($class, KafkaConsumer::class)) {
                return $class;
            }
        }

        return null;
    }
}
