<?php
namespace Minds\Core\Votes;

use Minds\Common\Repository\AbstractRepositoryOpts;

/**
 * @method string getEntityGuid()
 * @method self setEntityGuid(string $entityGuid)
 * @method string getLimit()
 * @method self setLimit(int $limit)
 * @method string getDirection()
 * @method self setDirection(string $direction)
 * @method string getPagingToken()
 * @method self setPagingToken(string $pagingToken)
 */
class VoteListOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $entityGuid;

    /** @var string */
    protected $direction;

    /** @var int */
    protected $limit;

    /** @var string */
    protected $pagingToken;
}
