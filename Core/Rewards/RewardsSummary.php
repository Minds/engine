<?php
namespace Minds\Core\Rewards;

use Minds\Traits\MagicAttributes;
use Brick\Math\BigDecimal;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setDateTs(int $unixTs)
 * @method int getDateTs()
 * @method self setRewardEntries(RewardEntry[] $rewardEntries)
 * @method RewardEntry[] getRewardEntries()
 */
class RewardsSummary
{
    use MagicAttributes;

    /** @var string */
    private $userGuid;

    /** @var int */
    private $dateTs;

    /** @var RewardEntry[] */
    private $rewardEntries;

    /**
     * Returns the totals
     * @return array
     */
    public function getTotals(): array
    {
        return [
            'daily' => BigDecimal::sum(...array_map(function ($rewardEntry) {
                return $rewardEntry->getTokenAmount();
            }, $this->rewardEntries)),
            'alltime' => BigDecimal::sum(...array_map(function ($rewardEntry) {
                return $rewardEntry->getAllTimeSummary()->getTokenAmount();
            }, $this->rewardEntries)),
        ];
    }

    /**
     * @return array
     */
    public function export($extras = []): array
    {
        $export = [
            'user_guid' => (string) $this->userGuid,
            'date' => date('Y-m-d', $this->dateTs),
            'date_iso8601' => date('c', $this->dateTs),
            'date_unixts' => $this->dateTs,
            'total' => $this->getTotals(),
        ];

        foreach ($this->rewardEntries as $rewardEntry) {
            $export[$rewardEntry->getRewardType()] = $rewardEntry->export();
        }

        return $export;
    }
}
