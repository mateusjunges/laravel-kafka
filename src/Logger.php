<?php

namespace Junges\Kafka;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\UidProcessor;
use RdKafka\Message;
use Throwable;

class Logger
{
    private MonologLogger $logger;

    public function __construct()
    {
        $handler = new StreamHandler("php://stdout");

        $handler->setFormatter(new JsonFormatter());
        $handler->pushProcessor(new UidProcessor(32));

        $this->logger = new MonologLogger('PHP-KAFKA-CONSUMER-ERROR');
        $this->logger->pushHandler($handler);
        $this->logger->pushProcessor(function ($record) {
            $record['datetime']->format('c');

            return $record;
        });
    }

    /**
     * Log an error message.
     *
     * @param Message $message
     * @param \Throwable|null $e
     * @param string $prefix
     */
    public function error(Message $message, Throwable $e = null, string $prefix = 'ERROR'): void
    {
        $this->logger->error("[{$prefix}] Error to consume message", [
            'message' => $message,
            'throwable' => $e,
        ]);
    }
}
