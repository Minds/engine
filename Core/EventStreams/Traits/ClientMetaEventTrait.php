<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Traits;

trait ClientMetaEventTrait
{
    public ?string $cmPlatform = null;
    public ?string $cmSource = null;
    public ?string $cmSalt = null;
    public ?string $cmMedium = null;
    public ?string $cmCampaign = null;
    public ?string $cmPageToken = null;
    public ?int $cmDelta = null;
    public ?int $cmPosition = null;
    public ?string $cmServedByGuid = null;
}
