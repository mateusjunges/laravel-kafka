<?php declare(strict_types=1);

namespace Junges\Kafka\Concerns;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Events\MessageConsumed;
use Junges\Kafka\Events\MessageFailed;
use Junges\Kafka\Events\MessageSkipped;
use Junges\Kafka\Events\RetryingMessage;
use Junges\Kafka\Exceptions\ConsumerException;
use RdKafka\Message;
use Throwable;

/**
 * The message pipeline shared by the consumer and the faked consumer, so both handle messages the
 * same way: the handler is retried while it fails, and a message that still fails is sent to the
 * dead letter queue, skipped, or stops the consumer. Classes using it provide the $config,
 * $dispatcher and $stopRequested properties.
 */
trait ProcessesMessages
{
    /** Send a failed message to the dead letter queue. */
    abstract private function sendToDeadLetterQueue(ConsumerMessage $message, Throwable $throwable, ?Message $kafkaMessage): void;

    /** Log an error about a message. Only messages received from Kafka can be logged. */
    private function logError(?Message $kafkaMessage, Throwable $throwable, string $prefix = 'ERROR'): void {}

    /**
     * The handler is called again while it fails and has retries left, waiting for the backoff between
     * attempts, with a copy of the message holding the attempt number. Retries stop early when the
     * consumer is asked to stop consuming.
     *
     * @throws ConsumerException
     */
    private function processMessage(ConsumerMessage $message, ?Message $kafkaMessage = null): void
    {
        $handledMessage = null;

        try {
            retry(
                $this->config->getFailedMessageRetries() + 1,
                function (int $attempt) use ($message, &$handledMessage): void {
                    $handledMessage = $message->withAttempts($attempt);

                    $this->config->getHandler()->handle($handledMessage, $this);
                },
                $this->config->getFailedMessageRetrySleep(),
                function (Throwable $throwable) use ($kafkaMessage, &$handledMessage): bool {
                    $this->logError($kafkaMessage, $throwable, 'RETRY');

                    if ($this->stopRequested) {
                        return false;
                    }

                    $this->dispatcher->dispatch(new RetryingMessage($handledMessage, $throwable, $this));

                    return true;
                },
            );

            $this->dispatcher->dispatch(new MessageConsumed($handledMessage, $this));
        } catch (Throwable $throwable) {
            // The failed message is the one the handler last received.
            $this->handleFailedMessage($handledMessage ?? $message, $throwable, $kafkaMessage);
        }
    }

    /**
     * The failure callback can't change what happens to the failed message, so an exception thrown
     * by it is reported instead of stopping the consumer. Without a dead letter queue, the consumer
     * stops and the offset of the failed message is left uncommitted, so it is consumed again once
     * a consumer resumes from its partition. Skipping the message must be explicitly enabled.
     *
     * @throws ConsumerException
     */
    private function handleFailedMessage(ConsumerMessage $message, Throwable $throwable, ?Message $kafkaMessage = null): void
    {
        $this->logError($kafkaMessage, $throwable);
        report($throwable);

        $this->dispatcher->dispatch(new MessageFailed($message, $throwable, $this));

        try {
            $this->config->getHandler()->failed($message, $throwable);
        } catch (Throwable $callbackException) {
            $this->logError($kafkaMessage, $callbackException, 'FAILURE_CALLBACK');
            report($callbackException);
        }

        if ($this->config->shouldSendToDlq()) {
            $this->sendToDeadLetterQueue($message, $throwable, $kafkaMessage);
        } elseif ($this->config->shouldSkipFailedMessages()) {
            $this->dispatcher->dispatch(new MessageSkipped($message, $throwable, $this));
        } else {
            throw ConsumerException::stoppedOnFailure($message, $throwable);
        }
    }
}
