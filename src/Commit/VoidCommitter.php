<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Contracts\Committer;

class VoidCommitter implements Committer
{
    public function commit(mixed $messageOrOffsets = null): void {}

    public function commitAsync(mixed $messageOrOffsets = null): void {}
}
