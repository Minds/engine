<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Traits;

trait ClientMetaEventTrait
{
    public ?string $cm_platform = null;
    public ?string $cm_source = null;
    public ?int $cm_timestamp = null;
    public ?string $cm_salt = null;
    public ?string $cm_medium = null;
    public ?string $cm_campaign = null;
    public ?string $cm_page_token = null;
    public ?int $cm_delta = null;
    public ?int $cm_position = null;
    public ?string $cm_served_by_guid = null;
}
