<?php

namespace Junges\Kafka\Contracts;

/**
 * @internal
 */
interface InteractsWithConfigCallbacks
{
    /**
     * Set the configuration error callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withErrorCb(callable $callback): self;

    /**
     * Sets the delivery report callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withDrMsgCb(callable $callback): self;

    /**
     * Set consume callback to use with poll.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withConsumeCb(callable $callback): self;

    /**
     * Set the log callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withLogCb(callable $callback): self;
}
