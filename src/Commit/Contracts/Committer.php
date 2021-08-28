<?php

namespace Junges\Kafka\Commit\Contracts;

interface Committer
{
    public function commitMessage(): void;

    public function commitDlq(): void;
}
