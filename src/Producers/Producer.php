<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Junges\Kafka\Concerns\ManagesTransactions;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\Producer as ProducerContract;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Events\MessagePublished;
use Junges\Kafka\Events\PublishingMessage;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use RdKafka\Conf;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;

class Producer implements ProducerContract
{
    use ManagesTransactions;

    public bool $transactionInitialized = false;

    private readonly KafkaProducer $producer;

    private readonly Dispatcher $dispatcher;

    public function __construct(
        private readonly Config $config,
        private readonly MessageSerializer $serializer,
        private readonly bool $async = false,
    ) {
        $this->producer = app(KafkaProducer::class, [
            'conf' => $this->getConf($this->config->getProducerOptions()),
        ]);
        $this->dispatcher = App::make(Dispatcher::class);
    }

    public function __destruct()
    {
        if ($this->async) {
            $this->flush();
        }
    }

    /** {@inheritDoc} */
    public function produce(ProducerMessage $message): bool
    {
        $this->dispatcher->dispatch(new PublishingMessage($message));

        $topic = $this->producer->newTopic($message->getTopicName());

        $message = clone $message;

        $message = $this->serializer->serialize($message);

        $this->produceMessage($topic, $message);

        $this->producer->poll(0);

        if ($this->async) {
            return true;
        }

        return $this->flush();
    }

    /**
     * @throws CouldNotPublishMessage
     * @throws Exception
     */
    public function flush(): mixed
    {
        // Here we define the flush callback that is called shutting down a consumer.
        // This is called after every single message sent using Producer::send
        $flush = function () {
            $sleepMilliseconds = config('kafka.flush_retry_sleep_in_ms', 100);
            $retries = $this->config->flushRetries ?? config('kafka.flush_retries', 10);
            $timeout = $this->config->flushTimeoutInMs ?? config('kafka.flush_timeout_in_ms', 1000);

            try {
                return retry($retries, function () use ($timeout) {
                    $result = $this->producer->flush($timeout);

                    if ($result === RD_KAFKA_RESP_ERR_NO_ERROR) {
                        return true;
                    }

                    $message = rd_kafka_err2str($result);

                    throw CouldNotPublishMessage::withMessage($message, $result);
                }, $sleepMilliseconds);
            } catch (CouldNotPublishMessage $exception) {
                $this->dispatcher->dispatch(new \Junges\Kafka\Events\CouldNotPublishMessage(
                    $exception->getKafkaErrorCode(),
                    $exception->getMessage(),
                    $exception,
                ));

                throw $exception;
            }
        };

        return $flush();
    }

    /** Set the Kafka Configuration. */
    private function getConf(array $options): Conf
    {
        $conf = new Conf;

        foreach ($options as $key => $value) {
            $conf->set($key, (string) $value);
        }

        foreach ($this->config->getConfigCallbacks() as $method => $callback) {
            $conf->{$method}($callback);
        }

        return $conf;
    }

    private function produceMessage(ProducerTopic $topic, ProducerMessage $message): void
    {
        $topic->producev(
            partition: $message->getPartition(),
            msgflags: RD_KAFKA_MSG_F_BLOCK,
            payload: $message->getBody(),
            key: $message->getKey(),
            headers: $message->getHeaders()
        );

        $this->dispatcher->dispatch(new MessagePublished($message));
    }
}
