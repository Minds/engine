<?php
namespace Minds\Core\Subscriptions\Graph;

use Minds\Traits\MagicAttributes;

/**
 * Repository get() method options
 * @package Minds\Core\Subscriptions\Graph
 * @method string getType()
 * @method RepositoryGetOptions setType(string $type)
 * @method string getUserGuid()
 * @method RepositoryGetOptions setUserGuid(string $userGuid)
 * @method string getPageToken()
 * @method RepositoryGetOptions setPageToken(string $pageToken)
 * @method string getSearchQuery()
 * @method RepositoryGetOptions setSearchQuery(string $searchQuery)
 * @method int getLimit()
 * @method RepositoryGetOptions setLimit(int $limit)
 * @method int getOffset()
 * @method RepositoryGetOptions setOffset(int $offset)
 */
class RepositoryGetOptions
{
    use MagicAttributes;

    /** @var string */
    protected $type;

    /** @var string */
    protected $userGuid;

    /** @var string */
    protected $pageToken;

    /** @var string */
    protected $searchQuery;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;
}
