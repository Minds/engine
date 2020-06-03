<?php
namespace Minds\Core\Wire\SupportTiers;

use Exception;
use Minds\Entities\User;
use Minds\Helpers\Log;

/**
 * User wire_rewards polyfill manager for Support Tiers
 * @package Minds\Core\Wire\SupportTiers
 * @deprecated
 * @todo Remove if no longer needed
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
     * @deprecated
     * @todo Remove if no longer needed
     */
    public function process(iterable $supportTiers): array
    {
        Log::notice('Support Tier migration is deprecated');
        // if (!$supportTiers) {
        //     return [];
        // }
        //
        // return $this->userWireRewardsMigrationDelegate->polyfill($supportTiers);
    }
}
