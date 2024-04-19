<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Notifications\Events;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;

class ChatNotificationEvent implements EventInterface
{
    use TimebasedEventTrait;

    public function __construct(
        public readonly string $entityUrn,
        public readonly int $fromGuid
    ) {
    }
}
