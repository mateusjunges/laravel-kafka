<?php declare(strict_types=1);

namespace Junges\Kafka\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Override;

class MakeConsumerCommand extends GeneratorCommand
{
    /* @var string $name */
    protected $name = 'make:kafka-consumer';

    /* @var string $description */
    protected $description = 'Create a new Kafka consumer class';

    /* @var string $type */
    protected $type = 'Kafka consumer';

    #[Override]
    protected function getStub(): string
    {
        return __DIR__.'/stubs/kafka-consumer.stub';
    }

    #[Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Kafka\\Consumers';
    }
}
