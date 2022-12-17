<?php

namespace Junges\Kafka\Concerns;

trait InteractsWithConfigCallbacks
{
    protected array $callbacks = [];

    /**
     * Set the configuration error callback.
     *
     * @return $this
     */
    public function withErrorCb(callable $callback): self
    {
        $this->callbacks['setErrorCb'] = $callback;

        return $this;
    }

    /**
     * Sets the delivery report callback.
     *
     * @return $this
     */
    public function withDrMsgCb(callable $callback): self
    {
        $this->callbacks['setDrMsgCb'] = $callback;

        return $this;
    }

    /**
     * Set consume callback to use with poll.
     *
     * @return $this
     */
    public function withConsumeCb(callable $callback): self
    {
        $this->callbacks['setConsumeCb'] = $callback;

        return $this;
    }

    /**
     * Set the log callback.
     *
     * @return $this
     */
    public function withLogCb(callable $callback): self
    {
        $this->callbacks['setLogCb'] = $callback;

        return $this;
    }

    /**
     * Set offset commit callback to use with consumer groups.
     *
     * @return $this
     */
    public function withOffsetCommitCb(callable $callback): self
    {
        $this->callbacks['setOffsetCommitCb'] = $callback;

        return $this;
    }

    /**
     * Set rebalance callback for  use with coordinated consumer group balancing.
     *
     * @return $this
     */
    public function withRebalanceCb(callable $callback): self
    {
        $this->callbacks['setRebalanceCb'] = $callback;

        return $this;
    }

    /**
     * Set statistics callback.
     *
     * @return $this
     */
    public function withStatsCb(callable $callback): self
    {
        $this->callbacks['setStatsCb'] = $callback;

        return $this;
    }
}
