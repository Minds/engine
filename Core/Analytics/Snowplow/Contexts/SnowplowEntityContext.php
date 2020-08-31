<?php
namespace Minds\Core\Analytics\Snowplow\Contexts;

use Minds\Traits\MagicAttributes;

/**
 * @method SnowplowActionEvent setEntityGuid(string $entityGuid)
 * @method SnowplowActionEvent setEntityType(string $entityType)
 * @method SnowplowActionEvent setEntitySubtype(string $entitySubtype)
 * @method SnowplowActionEvent setEntityOwnerGuid(string $entityOwnerGuid)
 * @method SnowplowActionEvent setEntityAccessId(int $accessId)
 * @method SnowplowActionEvent setEntityContainerGuid(string $entityContainerGuid)
 */
class SnowplowEntityContext implements SnowplowContextInterface
{
    use MagicAttributes;

    /** @var string */
    protected $entityGuid;

    /** @var string */
    protected $entityType;

    /** @var string */
    protected $entitySubtype;

    /** @var string */
    protected $entityOwnerGuid;

    /** @var int */
    protected $entityAccessId;

    /** @var string */
    protected $entityContainerGuid;

    /**
     * Returns the schema
     */
    public function getSchema(): string
    {
        return "iglu:com.minds/entity_context/jsonschema/1-0-0";
    }

    /**
     * Returns the sanitized data
     * null values are removed
     * @return array
     */
    public function getData(): array
    {
        return array_filter([
            'entity_guid' => (string) $this->entityGuid,
            'entity_type' => $this->entityType,
            'entity_subtype' => $this->entitySubtype,
            'entity_owner_guid' => (string) $this->entityOwnerGuid,
            'entity_access_id' => (string) $this->entityAccessId,
            'entity_container_guid' => (string) $this->entityContainerGuid
        ]);
    }
}
