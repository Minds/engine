<?php

namespace Minds\Core\Boost\V3\EventStreams\Models;

use Minds\Core\EventStreams\Traits\ClientMetaEventTrait;

class ViewPulsarMessage
{
    public string $userGuid;
    public string $entityUrn;
    public string $entityGuid;
    public string $entityOwnerGuid;
    public ?string $entityType = null;
    public ?string $entitySubType = null;

    use ClientMetaEventTrait;
}
