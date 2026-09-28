<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface InteractsWithConfigCallbacks
{
    /** Set a callback for errors reported by librdkafka. */
    public function onError(callable $callback): self;

    /** Set a callback for the log messages of librdkafka. */
    public function onLog(callable $callback): self;

    /** Set a callback for the statistics emitted by librdkafka every "statistics.interval.ms". */
    public function onStatistics(callable $callback): self;

    /** Set a callback for consumer group rebalances, which replaces the default partition assignment. */
    public function onRebalance(callable $callback): self;

    /** Set a callback for the result of offset commits. */
    public function onOffsetCommit(callable $callback): self;

    /** Set a callback that provides a new token when OAUTHBEARER authentication needs one. */
    public function onOAuthBearerTokenRefresh(callable $callback): self;
}
