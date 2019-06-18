<?php
/**
 * Trigger referral update
 */
namespace Minds\Core\Rewards\Join\Delegates;

use Minds\Core\Di\Di;

class ReferralDelegate 
{

    /** @var Manager $manager */
    private $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?: Di::_()->get('Core\Referrals\Manager');
    }

    /**
     * Update a referral via Referral Manager
     * @param Referral $referral
     * @return void
     */
    public function update($referral)
    {
        $this->manager->update($referral);

    }

}