<?php declare(strict_types=1);

namespace Junges\Kafka\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\InteractsWithTime;

class RestartConsumersCommand extends Command
{
    use InteractsWithTime;

    /** @var string */
    protected $signature = 'kafka:restart-consumers';

    /** @var string */
    protected $description = 'Restart all Kafka consumers.';

    public function handle()
    {
        Cache::driver(config('kafka.cache_driver'))->forever('laravel-kafka:consumer:restart', $this->currentTime());
        $this->info('Kafka consumers restart signal sent.');
    }
}
