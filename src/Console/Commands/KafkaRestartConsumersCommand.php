<?php

namespace Junges\Kafka\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\InteractsWithTime;

class KafkaRestartConsumersCommand extends Command
{
    use InteractsWithTime;
    
    protected $signature = 'kafka:restart-consumers';

    protected $description = 'Restart all Kafka consumers.';

    public function handle()
    {
        Cache::forever('laravel-kafka:consumer:restart', $this->currentTime());
        $this->info('Kafka consumers restart signal sent.');
    }
}
