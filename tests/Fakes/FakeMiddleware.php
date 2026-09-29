<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Fakes;

use Illuminate\Contracts\Config\Repository;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Middleware;

/** A middleware with a constructor dependency, which records the messages it receives. */
final class FakeMiddleware implements Middleware
{
    /** @var list<ConsumerMessage> */
    public static array $messages = [];

    public function __construct(public readonly Repository $config) {}

    public function __invoke(ConsumerMessage $message, callable $next): mixed
    {
        self::$messages[] = $message;

        return $next($message);
    }
}
