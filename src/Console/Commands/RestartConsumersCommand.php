<?php declare(strict_types=1);

namespace Junges\Kafka\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\InteractsWithTime;
use Junges\Kafka\Console\Commands\Concerns\ResolvesConsumerClasses;
use Junges\Kafka\Consumers\Consumer;

class RestartConsumersCommand extends Command
{
    use InteractsWithTime;
    use ResolvesConsumerClasses;

    /** @var string */
    protected $signature = 'kafka:restart-consumers
            {consumers?* : The names of the consumers to restart, or the names of their classes. Every consumer is restarted when none is given}';

    /** @var string */
    protected $description = 'Restart Kafka consumers.';

    public function handle(): void
    {
        $cache = Cache::driver(config('kafka.cache_driver'));
        $consumers = $this->argument('consumers');

        if ($consumers === []) {
            $cache->forever(Consumer::restartCacheKey(), $this->currentTime());
            $this->info('Kafka consumers restart signal sent.');

            return;
        }

        foreach ($consumers as $consumer) {
            $cache->forever(Consumer::restartCacheKey($this->consumerName($consumer)), $this->currentTime());
        }

        $this->info('Kafka consumers restart signal sent to: '.implode(', ', $consumers).'.');
    }

    /** Consumer classes are restarted using their name, which is their class name unless they override it. */
    private function consumerName(string $consumer): string
    {
        $class = $this->resolveConsumerClass($consumer);

        return $class === null ? $consumer : $this->laravel->make($class)->name();
    }
}
