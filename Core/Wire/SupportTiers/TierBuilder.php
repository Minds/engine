<?php
namespace Minds\Core\Wire\SupportTiers;

use Exception;

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
     * Unique-for-entity legacy persistent GUID (from wire_rewards).
     * Should not be used outside URN!
     * @param SupportTier $supportTier
     * @return int
     * @throws Exception
     */
    public function buildGuid(SupportTier $supportTier): int
    {
        $usd = $supportTier->getUsd();
        $scale = pow(10, 16);
        $amountScale = pow(10, 6);

        if (!$usd) {
            throw new Exception('Invalid USD value');
        }

        return $scale + floor($usd * $amountScale);
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
