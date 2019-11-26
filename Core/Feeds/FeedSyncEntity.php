<?php

namespace Minds\Core\Feeds;

use Minds\Traits\Exportable;
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
class FeedSyncEntity
{
    use MagicAttributes;
    use Exportable;

    /** @var int|string */
    protected $guid;

    /** @var int|string */
    protected $ownerGuid;

    /** @var int */
    protected $timestamp;

    /** @var string */
    protected $urn;

    /**
     * Specifies the exportable properties
     * @return array<string|\Closure>
     */
    public function export(): array
    {
        return [
            'urn',
            'guid',
            'ownerGuid',
            'timestamp',
        ];
    }
}
