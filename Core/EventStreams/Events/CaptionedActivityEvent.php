<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Events;

use Minds\Core\EventStreams\AcknowledgmentEventTrait;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;
use Minds\Traits\MagicAttributes;

/**
 * @method self setActivityUrn(string $activity_urn)
 * @method self setGuid(int $guid)
 * @method self setType(string $type)
 * @method self setContainerGuid(int|null $container_guid)
 * @method self setOwnerGuid(int|null $owner_guid)
 * @method self setAccessId(int|null $access_id)
 * @method self setTimeCreated(string|null $time_created)
 * @method self setTimePublished(string|null $time_published)
 * @method self setTags(string|null $tags)
 * @method self setMessage(string|null $message)
 * @method self setCaption(string $caption)
 * @method string getCaption()
 * @method string getActivityUrn()
 * @method int getGuid()
 * @method getType()
 */
class CaptionedActivityEvent implements EventInterface
{
    use MagicAttributes;
    use AcknowledgmentEventTrait;
    use TimebasedEventTrait;

    private string $activityUrn;
    private int $guid;
    private string $type;
    private ?int $containerGuid;
    private ?int $ownerGuid;
    private ?int $accessId;
    private ?string $timePublished;
    private ?string $timeCreated;
    private ?string $tags;
    private ?string $message;
    private string $caption;
}
