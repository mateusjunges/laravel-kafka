<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use Junges\Kafka\Producers\MessageBatch;

interface Producer
{
    /**
     * Produce the specified message in the kafka topic.
     *
     * @return mixed
     * @throws \Exception
     */
    public function produce(ProducerMessage $message): bool;

    /**
     * @throws CouldNotPublishMessage
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessageBatch
     */
    public function produceBatch(MessageBatch $messageBatch): int;

    /**
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException
     */
    public function beginTransaction(int $timeoutInMilliseconds = 1000): void;

    /**
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException
     */
    public function abortTransaction(int $timeoutInMilliseconds = 1000): void;

    /**
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException
     */
    public function commitTransaction(int $timeoutInMilliseconds = 1000): void;

    /** Used to properly shut down the producer. */
    public function flush(): mixed;
}
