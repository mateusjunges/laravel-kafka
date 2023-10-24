<?php declare(strict_types=1);

namespace Junges\Kafka\Concerns;

use Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException;
use Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException;
use Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException;
use RdKafka\KafkaErrorException;

/** @mixin \Junges\Kafka\Producers\Producer */
trait ManagesTransactions
{
    /**
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException
     */
    public function beginTransaction(int $timeoutInMilliseconds = 1000): void
    {
        try {
            if (! $this->transactionInitialized) {
                $this->producer->initTransactions($timeoutInMilliseconds);
                $this->transactionInitialized = true;
            }

            $this->producer->beginTransaction();
        } catch (KafkaErrorException $exception) {
            $this->handleTransactionException($exception);
        }
    }

    /**
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException
     */
    public function abortTransaction(int $timeoutInMilliseconds = 1000): void
    {
        try {
            $this->producer->abortTransaction($timeoutInMilliseconds);
        } catch (KafkaErrorException $exception) {
            $this->handleTransactionException($exception);
        }
    }

    /**
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException
     */
    public function commitTransaction(int $timeoutInMilliseconds = 1000): void
    {
        try {
            $this->producer->commitTransaction($timeoutInMilliseconds);
        } catch (KafkaErrorException $exception) {
            $this->handleTransactionException($exception);
        }
    }

    /**
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException
     * @throws \Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException
     */
    private function handleTransactionException(KafkaErrorException $exception): void
    {
        if ($exception->isRetriable() === true) {
            throw TransactionShouldBeRetriedException::new($exception);
        }

        if ($exception->transactionRequiresAbort() === true) {
            throw TransactionShouldBeAbortedException::new($exception);
        }

        $this->transactionInitialized = false;

        throw TransactionFatalErrorException::new($exception);
    }
}
