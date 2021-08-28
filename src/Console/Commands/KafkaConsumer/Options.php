<?php

namespace Junges\Kafka\Console\Commands\KafkaConsumer;

class Options
{
    private array $topics;
    private string $consumer;
    private string $groupId;
    private int $commit;
    private string $dlq;
    private int $maxMessage;
    private array $config;

    public function __construct(array $options, array $config)
    {
        if (is_string($options['topic'])) {
            $options['topic'] = [$options['topic']];
        }

        $this->config = $config;
        $this->topics = $options['topic'];
        $this->consumer = $options['consumer'];
        $this->groupId = $options['groupId'];
        $this->commit = $options['commit'];
        $this->dlq = $options['dlq'];
        $this->maxMessage = $options['maxMessage'];
    }

    public function getTopics(): array
    {
        return (is_array($this->topics) && ! empty($this->topics)) ? $this->topics : [];
    }

    public function getConsumer(): ?string
    {
        return $this->consumer;
    }

    public function getGroupId(): string
    {
        return (is_string($this->groupId) && strlen($this->groupId) > 1)
            ? $this->groupId
            : $this->config['groupId'];
    }

    public function getCommit(): ?string
    {
        return $this->commit;
    }

    public function getDlq(): ?string
    {
        return (is_string($this->dlq) && strlen($this->dlq) > 1) ? $this->dlq : null;
    }

    public function getMaxMessage(): int
    {
        return (is_int($this->maxMessage) && $this->maxMessage >= 1) ? $this->maxMessage : -1;
    }
}
