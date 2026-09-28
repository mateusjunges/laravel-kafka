<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface Producer
{
    /**
     * Queue the given message to be delivered to Kafka. The message is delivered
     * in the background, call flush() to wait until every message is delivered.
     */
    public function produce(ProducerMessage $message, ?MessageSerializer $serializer = null): void;

    /**
     * Wait until every queued message is delivered to Kafka.
     *
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     */
    public function flush(): void;

    /** Set a callback to be executed with the delivered messages after flushing. */
    public function withFlushCallback(callable $callback): self;

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
}
