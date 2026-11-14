<?php declare(strict_types=1);

namespace Junges\Kafka\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Console\Commands\Concerns\ResolvesConsumerClasses;
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Contracts\Manager;
use Junges\Kafka\KafkaConsumer;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ConsumerCommand extends Command
{
    use ResolvesConsumerClasses;

    /* @var string $signature */
    protected $signature = 'kafka:consume
            {consumer : The consumer class, either its fully qualified name or its name in the App\Kafka\Consumers namespace}
            {--max-messages= : Stop after handling the given number of messages}
            {--max-time= : Stop after the given number of seconds}
            {--stop-when-empty : Stop once there are no messages left in the assigned partitions}';

    /* @var string $description */
    protected $description = 'Consume Kafka messages using a consumer class.';

    public function handle(Manager $kafka): int
    {
        $consumer = $this->resolveConsumerClass($this->argument('consumer'));

        if ($consumer === null) {
            $this->components->error("The consumer [{$this->argument('consumer')}] does not exist or does not extend [".KafkaConsumer::class.'].');

            return SymfonyCommand::FAILURE;
        }

        $kafka->consumerFor($consumer)
            ->when($this->option('max-messages'), fn (Builder $builder, string $maxMessages) => $builder->stopAfterMessages((int) $maxMessages))
            ->when($this->option('max-time'), fn (Builder $builder, string $maxTime) => $builder->stopAfterSeconds((int) $maxTime))
            ->when($this->option('stop-when-empty'), fn (Builder $builder) => $builder->stopWhenEmpty())
            ->build()
            ->consume();

        return SymfonyCommand::SUCCESS;
    }
}
