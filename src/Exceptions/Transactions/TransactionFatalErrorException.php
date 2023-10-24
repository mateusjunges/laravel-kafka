<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions\Transactions;

use Junges\Kafka\Exceptions\LaravelKafkaException;
use RdKafka\KafkaErrorException;

final class TransactionFatalErrorException extends LaravelKafkaException
{
    private const FATAL_EXCEPTION_MESSAGE = 'Transaction failed with a fatal error. You must create a new producer as this one can not be used anymore. [%s]';

    public static function new(KafkaErrorException $baseException): self
    {
        return new self(
            sprintf(self::FATAL_EXCEPTION_MESSAGE, $baseException->getMessage()),
            $baseException->getCode(),
            $baseException
        );
    }
}
