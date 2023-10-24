<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions\Transactions;

use Junges\Kafka\Exceptions\LaravelKafkaException;
use RdKafka\KafkaErrorException;

final class TransactionShouldBeRetriedException extends LaravelKafkaException
{
    private const RETRIABLE_EXCEPTION_MESSAGE = 'This transaction failed, but can be retried. [%s]';

    public static function new(KafkaErrorException $baseException): self
    {
        return new self(
            sprintf(self::RETRIABLE_EXCEPTION_MESSAGE, $baseException->getMessage()),
            $baseException->getCode(),
            $baseException
        );
    }
}
