<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

/** Why a consumer stopped consuming. */
enum StopReason: string
{
    /** The consumer was asked to stop, through stopConsuming(). */
    case Requested = 'requested';

    /** The process received a SIGTERM, SIGINT or SIGQUIT signal. */
    case Signal = 'signal';

    /** Consumers were asked to restart, through the "kafka:restart-consumers" command. */
    case Restart = 'restart';

    /** There were no messages left in the assigned partitions, with stopWhenEmpty(). */
    case Empty = 'empty';

    /** The consumer handled the number of messages given to stopAfterMessages(). */
    case MessageLimit = 'message-limit';

    /** The consumer ran for the number of seconds given to stopAfterSeconds(). */
    case TimeLimit = 'time-limit';

    /** An exception was thrown while consuming, for instance by a failed message. */
    case Failed = 'failed';
}
