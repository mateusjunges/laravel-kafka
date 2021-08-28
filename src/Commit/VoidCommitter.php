<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Committer;

class VoidCommitter implements Committer
{
    public function commitMessage(): void
    {
    }

    public function commitDlq(): void
    {
    }
}
