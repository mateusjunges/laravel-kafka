<?php declare(strict_types=1);

namespace Junges\Kafka\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Console\Commands\KafkaConsumer\Options;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Contracts\Manager;
use Junges\Kafka\Contracts\MessageDeserializer;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ConsumerCommand extends Command
{
    /* @var string $signature */
    protected $signature = 'kafka:consume 
            {--topics= : The topics to listen for messages (topic1,topic2,...,topicN)} 
            {--consumer= : The consumer which will consume messages in the specified topic} 
            {--deserializer= : The deserializer class to use when consuming message}
            {--groupId=anonymous : The consumer group id} 
            {--dlq=? : The Dead Letter Queue} 
            {--maxMessage=? : The max number of messages that should be handled}
            {--maxTime=0 : The max number of seconds that a consumer should run }
            {--securityProtocol=?}
            {--connection= : The Kafka connection to use}';

    /* @var string $description */
    protected $description = 'A Kafka Consumer for Laravel.';

    public function handle(Manager $manager): int
    {
        if (empty($this->option('consumer'))) {
            $this->error('The [--consumer] option is required.');

            return SymfonyCommand::SUCCESS;
        }

        if (empty($this->option('topics'))) {
            $this->error('The [--topics option is required.');

            return SymfonyCommand::SUCCESS;
        }

        $connection = $manager->connection($this->option('connection'))->getConfig();

        $parsedOptions = array_map($this->parseOptions(...), $this->options());

        $options = new Options($parsedOptions, [
            'brokers' => $connection->brokers,
            'groupId' => $connection->groupId,
            'securityProtocol' => $connection->securityProtocol,
            'sasl' => [
                'mechanisms' => $connection->sasl?->getMechanisms(),
                'username' => $connection->sasl?->getUsername(),
                'password' => $connection->sasl?->getPassword(),
            ],
        ]);

        $consumer = $options->getConsumer();
        $deserializer = $options->getDeserializer();

        $config = new Config(
            broker: $options->getBroker(),
            topics: $options->getTopics(),
            securityProtocol: $options->getSecurityProtocol(),
            groupId: $options->getGroupId(),
            consumer: app($consumer),
            sasl: $options->getSasl(),
            dlq: $options->getDlq(),
            maxMessages: $options->getMaxMessages(),
            maxTime: $options->getMaxTime(),
            autoCommit: $connection->autoCommit,
            customOptions: [...$connection->options, ...$connection->consumerOptions],
            callbacks: $connection->callbacks,
            consumerTimeoutInMs: $connection->consumerTimeoutInMs,
        );

        /** @var Consumer $consumer */
        $consumer = app(Consumer::class, [
            'config' => $config,
            'deserializer' => app($deserializer ?? MessageDeserializer::class),
        ]);

        $consumer->consume();

        return SymfonyCommand::SUCCESS;
    }

    private function parseOptions(int|string|null $option): int|string|null
    {
        if ($option === '?') {
            return null;
        }

        if (is_numeric($option)) {
            return (int) $option;
        }

        return $option;
    }
}
