<?php
namespace Minds\Core\Wire\SupportTiers;

use Exception;
use Minds\Entities\User;
use Minds\Helpers\Log;

/**
 * User wire_rewards polyfill manager for Support Tiers
 * @package Minds\Core\Wire\SupportTiers
 */
class Polyfill
{
    /** @var Manager */
    protected $manager;

    /** @var Delegates\UserWireRewardsMigrationDelegate */
    protected $userWireRewardsMigrationDelegate;

    /**
     * Polyfill constructor.
     * @param $manager
     * @param $userWireRewardsMigrationDelegate
     */
    public function __construct(
        $manager = null,
        $userWireRewardsMigrationDelegate = null
    ) {
        $this->manager = $manager ?: new Manager();
        $this->userWireRewardsMigrationDelegate = $userWireRewardsMigrationDelegate ?: new Delegates\UserWireRewardsMigrationDelegate();
    }

    /**
     * Transforms a Support Tiers iterable into a wire_rewards compatible output.
     * @param SupportTier[] $supportTiers
     * @return array
     */
    public function process(iterable $supportTiers): array
    {
        if (!$supportTiers) {
            return [];
        }

        return $this->userWireRewardsMigrationDelegate->polyfill($supportTiers);
    }
}
