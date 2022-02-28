<?php

namespace Junges\Kafka\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Console\Commands\KafkaConsumer\Options;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Contracts\MessageDeserializer;

class KafkaConsumerCommand extends Command
{
    protected $signature = 'kafka:consume 
            {--topics= : The topics to listen for messages (topic1,topic2,...,topicN)} 
            {--consumer= : The consumer which will consume messages in the specified topic} 
            {--groupId=anonymous : The consumer group id} 
            {--commit=1} 
            {--dlq=? : The Dead Letter Queue} 
            {--maxMessage=? : The max number of messages that should be handled}
            {--securityProtocol=?}';

    protected $description = 'A Kafka Consumer for Laravel.';

    private array $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = [
            'brokers' => config('kafka.brokers'),
            'groupId' => config('kafka.consumer_group_id'),
            'securityProtocol' => config('kafka.securityProtocol'),
            'sasl' => [
                'mechanisms' => config('kafka.sasl.mechanisms'),
                'username' => config('kafka.sasl.username'),
                'password' => config('kafka.sasl.password'),
            ],
        ];
    }

    public function handle()
    {
        if (empty($this->option('consumer'))) {
            $this->error('The [--consumer] option is required.');

            return;
        }

        if (empty($this->option('topics'))) {
            $this->error('The [--topics option is required.');

            return;
        }
        $options = new Options($this->options(), $this->config);

        $consumer = $options->getConsumer();

        $config = new Config(
            broker: $options->getBroker(),
            topics: $options->getTopics(),
            securityProtocol: $options->getSecurityProtocol(),
            commit: $options->getCommit(),
            groupId: $options->getGroupId(),
            consumer: new $consumer(),
            sasl: $options->getSasl(),
            dlq: $options->getDlq(),
            maxMessages: $options->getMaxMessages()
        );

        /** @var Consumer $consumer */
        $consumer = app(Consumer::class, [
            'config' => $config,
            'deserializer' => app(MessageDeserializer::class),
        ]);

        $consumer->consume();
    }
}
