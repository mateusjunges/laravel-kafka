<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

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
