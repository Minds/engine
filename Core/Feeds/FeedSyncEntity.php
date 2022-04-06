<?php

/**
 * FeedSyncEntity.
 *
 * @author emi
 */

namespace Minds\Core\Feeds;

use JsonSerializable;
use Minds\Entities\Entity;
use Minds\Traits\MagicAttributes;

/**
 * Class FeedSyncEntity
 * @package Minds\Core\Feeds
 * @method int|string getGuid()
 * @method FeedSyncEntity setGuid(int|string $guid)
 * @method int|string getOwnerGuid()
 * @method FeedSyncEntity setOwnerGuid(int|string $ownerGuid)
 * @method int getTimestamp()
 * @method FeedSyncEntity setTimestamp(int $timestamp)
 * @method string getUrn()
 * @method FeedSyncEntity setUrn(string $urn)
 * @method Entity getEntity()
 * @method FeedSyncEntity setEntity(Entity $entity)
 */
class FeedSyncEntity implements JsonSerializable
{
    use MagicAttributes;

    /** @var int|string */
    protected $guid;

    /** @var int|string */
    protected $ownerGuid;

    /** @var int */
    protected $timestamp;

    /** @var string */
    protected $urn;

    /** @var Entity */
    protected $entity;

    /** @var bool */
    protected $deleted = false;

    /**
     * Makes properties accessible from outside
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * Export to public API
     * @return array
     */
    public function export()
    {
        return [
            'guid' => (string) $this->guid,
            'owner_guid' => (string) $this->ownerGuid,
            'timestamp' => $this->timestamp,
            'urn' => $this->urn,
            'entity' => $this->entity ? $this->entity->export() : null,
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->export();
    }
}
