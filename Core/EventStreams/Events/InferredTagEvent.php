<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Events;

use Minds\Core\EventStreams\AcknowledgmentEventTrait;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;

class InferredTagEvent implements EventInterface
{
    use AcknowledgmentEventTrait;
    use TimebasedEventTrait;

    public readonly array $inferredTags;

    public function __construct(
        public readonly string $activityUrn,
        public readonly int $guid,
        public readonly string $embedString,
        string|array $inferredTags
    ) {
        if (gettype($inferredTags) === "string") {
            $inferredTags = json_decode($inferredTags, true);
        }

        $this->inferredTags = $inferredTags;
    }
}
