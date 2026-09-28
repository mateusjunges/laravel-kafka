<?php declare(strict_types=1);

namespace Junges\Kafka\Concerns;

trait InteractsWithConfigCallbacks
{
    protected array $callbacks = [];

    /** Set the configuration error callback. */
    public function withErrorCb(callable $callback): self
    {
        return $this->setConfigCallback('setErrorCb', $callback);
    }

    /** Sets the delivery report callback. */
    public function withDrMsgCb(callable $callback): self
    {
        return $this->setConfigCallback('setDrMsgCb', $callback);
    }

    /** Set consume callback to use with poll. */
    public function withConsumeCb(callable $callback): self
    {
        return $this->setConfigCallback('setConsumeCb', $callback);
    }

    /** Set the log callback. */
    public function withLogCb(callable $callback): self
    {
        return $this->setConfigCallback('setLogCb', $callback);
    }

    /** Set offset commit callback to use with consumer groups. */
    public function withOffsetCommitCb(callable $callback): self
    {
        return $this->setConfigCallback('setOffsetCommitCb', $callback);
    }

    /** Set rebalance callback for  use with coordinated consumer group balancing. */
    public function withRebalanceCb(callable $callback): self
    {
        return $this->setConfigCallback('setRebalanceCb', $callback);
    }

    /** Set statistics callback. */
    public function withStatsCb(callable $callback): self
    {
        return $this->setConfigCallback('setStatsCb', $callback);
    }

    /** Set the OAUTHBEARER token refresh callback. */
    public function withOAuthBearerTokenRefreshCallback(callable $callback): self
    {
        return $this->setConfigCallback('setOauthbearerTokenRefreshCb', $callback);
    }

    /** Set a configuration callback, keyed by the \RdKafka\Conf method that sets it. */
    protected function setConfigCallback(string $method, callable $callback): self
    {
        $this->callbacks[$method] = $callback;

        return $this;
    }
}
