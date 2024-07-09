<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Events;

use Minds\Core\EventStreams\AcknowledgmentEventTrait;
use Minds\Core\EventStreams\EntityEventTrait;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;
use Minds\Core\EventStreams\Traits\ClientMetaEventTrait;

class ViewEvent implements EventInterface
{
    use EntityEventTrait;
    use AcknowledgmentEventTrait;
    use TimebasedEventTrait;
    use ClientMetaEventTrait;

    public string $viewUUID;
    public bool $external = false;
}
