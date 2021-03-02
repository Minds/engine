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
 * @method self setMultiplier(BigDecimal $multiplier)
 * @method BigDecimal getMultiplier()
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

    /** @var BigDecimal */
    private $multiplier;

    /** @var BigDecimal */
    private $tokenAmount;

    /** @var float */
    private $sharePct = 0;

    /** @var RewardEntry */
    private $allTimeSummary;

    /** @var RewardEntry */
    private $globalSummary;

    /** @var int */
    private $tokenomicsVersion = 2;

    public function __construct()
    {
        $this->tokenAmount = BigDecimal::of(0);
        $this->score = BigDecimal::of(0);
        $this->multiplier = BigDecimal::of(1);
    }

    /**
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'user_guid' => (string) $this->userGuid,
            'date' => date('Y-m-d', $this->dateTs),
            'date_iso8601' => date('c', $this->dateTs),
            'date_unixts' => $this->dateTs,
            'reward_type' => $this->rewardType,
            'score' => (string) $this->score,
            'raw_score' => $this->score->dividedBy($this->multiplier),
            'share_pct' => $this->sharePct,
            'multiplier' => $this->multiplier,
            'token_amount' => (string) $this->tokenAmount,
            'tokenomics_version' => $this->tokenomicsVersion,
            'alltime_summary' => [
                'score' => $this->allTimeSummary ? (string) $this->allTimeSummary->getScore() : 0,
                'token_amount' => $this->allTimeSummary ? (string) $this->allTimeSummary->getTokenAmount() : 0,
            ],
            'global_summary' => [
                'score' => $this->globalSummary ? (string) $this->globalSummary->getScore() : 0,
                'token_amount' => $this->globalSummary ? (string) $this->globalSummary->getTokenAmount() : 0,
            ],
        ];
    }
}
