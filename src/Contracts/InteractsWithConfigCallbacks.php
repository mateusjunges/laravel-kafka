<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

/** @internal */
interface InteractsWithConfigCallbacks
{
    /** Set the configuration error callback.  */
    public function withErrorCb(callable $callback): self;

    /** Sets the delivery report callback. */
    public function withDrMsgCb(callable $callback): self;

    /** Set consume callback to use with poll. */
    public function withConsumeCb(callable $callback): self;

    /** Set the log callback. */
    public function withLogCb(callable $callback): self;
}
