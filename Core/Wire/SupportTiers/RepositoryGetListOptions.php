<?php
namespace Minds\Core\Wire\SupportTiers;

use Minds\Traits\MagicAttributes;

/**
 * Repository::getList() options
 * @package Minds\Core\Wire\SupportTiers
 * @method string getEntityGuid()
 * @method RepositoryGetListOptions setEntityGuid(string $entityGuid)
 * @method string getGuid()
 * @method RepositoryGetListOptions setGuid(string $guid)
 * @method string getOffset()
 * @method RepositoryGetListOptions setOffset(string $offset)
 * @method int getLimit()
 * @method RepositoryGetListOptions setLimit(int $limit)
 */
class RepositoryGetListOptions
{
    use MagicAttributes;

    /** @var string */
    protected $entityGuid;

    /** @var string */
    protected $guid;

    /** @var string */
    protected $offset = '';

    /** @var int */
    protected $limit = 5000;
}
