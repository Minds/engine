<?php
/**
 * Trigger referral update
 */
namespace Minds\Core\Rewards\Delegates;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Referrals\Referral;
use Minds\Core\Rewards\Contributions;
use Minds\Core\Rewards\Contributions\Contribution;
use Minds\Core\Rewards\Contributions\ContributionValues;

class ReferralDelegate
{
    /** @var Manager $manager */
    private $manager;

    /** @var Contributions\Manager */
    protected $contributionsManager;

    public function __construct($manager = null, $contributionsManager = null)
    {
        $this->manager = $manager ?: Di::_()->get('Referrals\Manager');
        $this->contributionsManager = $contributionsManager ?? Di::_()->get('Rewards\Contributions\Manager');
    }

    /**
     * Update a referral via Referral Manager
     * @param Referral $referral
     * @return void
     */
    public function onReferral(User $user)
    {
        $referral = new Referral();
        $referral->setReferrerGuid((string) $user->referrer)
            ->setProspectGuid($user->guid)
            ->setJoinTimestamp(time());

        $this->manager->update($referral);

        // TODO: This should be in its own delegate?
        //$this->issueContributionScore($user);
    }

    /**
     * On reject, set the join_timestamp to -1, marking it as invalid.
     *
     * @param string $prospect_guid - guid of referral prospect
     * @param string $referrer_guid - original referrer.
     * @return void
     */
    public function onReject(string $prospect_guid, string $referrer_guid): void
    {
        $referral = new Referral();

        $referral->setReferrerGuid($referrer_guid)
            ->setProspectGuid($prospect_guid)
            ->setJoinTimestamp(-1);

        $this->manager->update($referral);
    }

    // /**
    //  * Issue contribution score when referred
    //  * TODO: Move to own delegate?
    //  * @param User $user
    //  * @return void
    //  */
    // private function issueContributionScore(User $user) : void
    // {
    //     $ts = strtotime('midnight') * 1000;
    //     $contribution = new Contribution();
    //     $contribution
    //         ->setMetric('referrals_welcome')
    //         ->setTimestamp($ts)
    //         ->setUser($user)
    //         ->setScore(ContributionValues::$multipliers['referrals_welcome'])
    //         ->setAmount(1);
    //     $this->contributionsManager->add($contribution);
    // }
}
