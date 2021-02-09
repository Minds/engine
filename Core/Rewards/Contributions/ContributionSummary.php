<?php
namespace Minds\Core\Rewards\Contributions;

use Minds\Traits\MagicAttributes;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setDateTs(int $unixTs)
 * @method int getDateTs()
 * @method self setScore(int $score)
 * @method int getScore()
 * @method self setAmount(int $amount)
 * @method int getAmount()
 */
class ContributionSummary
{
    use MagicAttributes;

    /** @var string */
    private $userGuid;

    /** @var int */
    private $dateTs;

    /** @var int */
    private $score;

    /** @var int */
    private $amount;
}
