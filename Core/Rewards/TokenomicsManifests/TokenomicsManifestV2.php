<?php
namespace Minds\Core\Rewards\TokenomicsManifests;

use Minds\Core\Rewards\Manager;

class TokenomicsManifestV2 implements TokenomicsManifestInterface
{
    /**
     * Returns the daily reward pools
     * @return array
     */
    public function getDailyPools(): array
    {
        return [
            Manager::REWARD_TYPE_ENGAGEMENT => 4000,
            Manager::REWARD_TYPE_HOLDING => 1000,
            MANAGER::REWARD_TYPE_LIQUIDITY => 5000,
        ];
    }

    /**
     * @return int
     */
    public function getMaxMultiplier(): int
    {
        return 3;
    }

    /**
     * @return int
     */
    public function getMinMultiplier(): int
    {
        return 1;
    }

    /**
     * @return int
     */
    public function getMaxMultiplierDays(): int
    {
        return 365; // 1 year
    }
}
