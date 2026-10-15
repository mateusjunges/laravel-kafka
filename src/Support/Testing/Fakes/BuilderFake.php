<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Contracts\Consumer as ConsumerContract;
use Junges\Kafka\Contracts\ConsumerMessage;
use Override;

class BuilderFake extends Builder
{
    /** @var list<ConsumerMessage> */
    private array $messages = [];

    /** Set the messages the faked consumer receives. */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    /** Build a consumer that handles the given messages instead of consuming from Kafka. */
    #[Override]
    public function build(): ConsumerContract
    {
        return new ConsumerFake($this->makeConfig(), $this->messages);
    }
}
