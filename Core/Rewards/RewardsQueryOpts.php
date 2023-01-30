<?php
namespace Minds\Core\Rewards;

use Minds\Common\Repository\AbstractRepositoryOpts;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setDateTs(int $unixTs)
 * @method int getDateTs()
 * @method self setRecalculate(bool $recalculate)
 * @method bool isRecalculate()
 */
class RewardsQueryOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $userGuid;

    /** @var int */
    protected $dateTs = 0;

    /** @var bool */
    protected $recalculate = false;
}
