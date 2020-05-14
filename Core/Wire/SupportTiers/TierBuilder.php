<?php
namespace Minds\Core\Wire\SupportTiers;

/**
 * Tier names helper
 * @package Minds\Core\Wire\SupportTiers
 */
class TierBuilder
{
    /**
     * Tier names. Last one will have `+` appended in case there are more tiers.
     */
    const TIER_NAMES = [
        'Bronze',
        'Silver',
        'Gold',
        'Platinum',
    ];

    /**
     * Sorts a reward array by amount, needed to correctly build tier name
     * @param $a
     * @param $b
     * @return int
     */
    public function sortRewards($a, $b): int
    {
        return (($a['amount'] ?? 0) < ($b['amount'] ?? 0)) ? -1 : 1;
    }

    /**
     * Unique-for-entity legacy persistent GUID (from wire_rewards).
     * Should not be used outside URN!
     * @param int $baseGuid
     * @param string $currency
     * @param float $amount
     * @return int
     */
    public function buildGuid(string $currency, float $amount): int
    {
        $guid = 0;

        switch ($currency) {
            case 'tokens':
                $guid += 1 * pow(10, 16);
                break;

            case 'usd':
                $guid += 2 * pow(10, 16);
                break;
        }

        $guid += floor($amount * pow(10, 6));

        return $guid;
    }

    /**
     * Builds a tier name based on its index
     * @param int $index
     * @return string
     */
    public function buildName(int $index): string
    {
        if ($index < count(static::TIER_NAMES)) {
            return static::TIER_NAMES[$index];
        } else {
            $plus = count(static::TIER_NAMES) - $index + 1;
            $suffix = str_repeat('+', $plus);

            return static::TIER_NAMES[count(static::TIER_NAMES) - 1] . $suffix;
        }
    }
}
