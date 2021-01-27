<?php
namespace Minds\Core\Rewards;

use Minds\Traits\MagicAttributes;
use Brick\Math\BigDecimal;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setRewardType(string $rewardType)
 * @method string getRewardType()
 * @method self setDateTs(int $unixTs)
 * @method int getDateTs()
 * @method self setScore(BigDecimal $score)
 * @method BigDecimal getScore()
 * @method self setMultiplier(float $multiplier)
 * @method float getMultiplier()
 * @method self setTokenAmount(BigDecimal $tokenAmount)
 * @method BigDecimal getTokenAmount()
 * @method self setTokenomicVersion(int $tokenomicsVersion)
 * @method int getTokenomicsVersion()
 */
class RewardEntry
{
    use MagicAttributes;

    /** @var string */
    private $userGuid;

    /** @var string */
    private $rewardType;

    /** @var int */
    private $dateTs;

    /** @var BigDecimal */
    private $score;

    /** @var float */
    private $multiplier;

    /** @var BigDecimal */
    private $tokenAmount;

    /** @var float */
    private $sharePct;

    /** @var RewardEntry */
    private $allTimeSummary;

    /** @var int */
    private $tokenomicsVersion = 2;

    /**
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'user_guid' => (string) $this->userGuid,
            'date' => date('Y-m-d', $this->dateTs),
            'data_iso8601' => date('c', $this->dateTs),
            'reward_type' => $this->rewardType,
            'score' => (string) $this->score,
            'share_pct' => $this->sharePct,
            'multiplier' => (string) $this->multiplier,
            'token_amount' => (string) $this->tokenAmount,
            'tokenomics_version' => $this->tokenomicsVersion,
            'alltime_summary' => [
                'score' => $this->allTimeSummary ? (string) $this->allTimeSummary->getScore() : 0,
                'token_amount' => $this->allTimeSummary ? (string) $this->allTimeSummary->getTokenAmount() : 0,
            ],
        ];
    }
}
