<?php
namespace Minds\Core\Rewards\TokenomicsManifests;

interface TokenomicsManifestInterface
{
    /**
     * Returns the daily reward pools
     * @return array
     */
    public function getDailyPools(): array;

    /**
     * @return int
     */
    public function getMaxMultiplier(): int;

    /**
     * @return int
     */
    public function getMinMultiplier(): int;

    /**
     * @return int
     */
    public function getMaxMultiplierDays(): int;
}
