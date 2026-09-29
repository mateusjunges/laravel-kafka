<?php declare(strict_types=1);

namespace Junges\Kafka\Concerns;

trait InteractsWithConfigCallbacks
{
    protected array $callbacks = [];

    /** Set a callback for errors reported by librdkafka. */
    public function onError(callable $callback): self
    {
        return $this->setConfigCallback('setErrorCb', $callback);
    }

    /** Set a callback for the log messages of librdkafka. */
    public function onLog(callable $callback): self
    {
        return $this->setConfigCallback('setLogCb', $callback);
    }

    /** Set a callback for the statistics emitted by librdkafka every "statistics.interval.ms". */
    public function onStatistics(callable $callback): self
    {
        return $this->setConfigCallback('setStatsCb', $callback);
    }

    /** Set a callback for consumer group rebalances, which replaces the default partition assignment. */
    public function onRebalance(callable $callback): self
    {
        return $this->setConfigCallback('setRebalanceCb', $callback);
    }

    /** Set a callback for the result of offset commits. */
    public function onOffsetCommit(callable $callback): self
    {
        return $this->setConfigCallback('setOffsetCommitCb', $callback);
    }

    /** Set a callback that provides a new token when OAUTHBEARER authentication needs one. */
    public function onOAuthBearerTokenRefresh(callable $callback): self
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
