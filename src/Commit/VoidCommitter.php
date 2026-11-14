<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\Contracts\ConsumerMessage;
use RdKafka\Message;

class VoidCommitter implements Committer
{
    public function commit(ConsumerMessage|Message|array|null $messageOrOffsets = null): void {}

    public function commitAsync(ConsumerMessage|Message|array|null $messageOrOffsets = null): void {}
}
