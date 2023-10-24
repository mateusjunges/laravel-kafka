<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions\Transactions;

use Junges\Kafka\Exceptions\LaravelKafkaException;
use RdKafka\KafkaErrorException;

final class TransactionShouldBeAbortedException extends LaravelKafkaException
{
    private const ABORTABLE_EXCEPTION_MESSAGE = 'Transaction failed. You must abort your current transaction and start a new one. [%s]';

    public static function new(KafkaErrorException $baseException): self
    {
        return new self(
            sprintf(self::ABORTABLE_EXCEPTION_MESSAGE, $baseException->getMessage()),
            $baseException->getCode(),
            $baseException
        );
    }
}
