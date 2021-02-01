<?php
namespace Minds\Core\Rewards;

use Minds\Common\Repository\AbstractRepositoryOpts;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setDateTs(int $unixTs)
 * @method int getDateTs()
 */
class RewardsQueryOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $userGuid;

    /** @var int */
    protected $dateTs = 0;
}
