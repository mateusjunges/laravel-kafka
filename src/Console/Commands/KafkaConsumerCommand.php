<?php

namespace Junges\Kafka\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Commit\DefaultCommitterFactory;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Console\Commands\KafkaConsumer\Options;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\MessageCounter;

class KafkaConsumerCommand extends Command
{
    protected $signature = 'kafka:consume 
            {--topic*= : The topics to listen for messages (topic1,topic2,...,topicN)} 
            {--consumer=* : The consumer which will consume messages in the specified topic} 
            {--groupId=? : The consumer group id} 
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
            'broker' => config('kafka.brokers'),
            'groupId' => config('kafka.group_id'),
            'securityProtocol' => config('kafka.securityProtocol'),
            'sasl' => [
                'mechanisms' => config('kafka.sasl.mechanisms'),
                'username' => config('kafka.sasl.username'),
                'password' => config('kafka.sasl.password'),
            ],
        ];
    }

    /**
     * @throws \Carbon\Exceptions\Exception
     * @throws \RdKafka\Exception
     */
    public function handle()
    {
        $options = new Options($this->options(), $this->config);

        $consumer = $options->getConsumer();

        $config = new Config(
            broker: $this->config['broker'],
            topics: $options->getTopics(),
            securityProtocol: $options->getSecurityProtocol(),
            commit: $options->getCommit(),
            groupId: $options->getGroupId(),
            consumer: new $consumer(),
            sasl: $options->getSasl(),
            dlq: $options->getDlq(),
            maxMessages: $options->getMaxMessage()
        );

        (new Consumer($config, new JsonDeserializer(), (
            new DefaultCommitterFactory(
                new MessageCounter($config->getMaxMessages())
            )
        )))->consume();
    }
}
