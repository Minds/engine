<?php

namespace Spec\Minds\Core\Rewards\Delegates;

use Minds\Core\Rewards\Delegates\ReferralDelegate;
use Minds\Core\Referrals\Referral;
use Minds\Core\Referrals\Manager;
use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Core\Rewards\Contributions;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ReferralDelegateSpec extends ObjectBehavior
{
    /** @var Manager $manager */
    private $manager;

    /** @var $contributionsManager */
    private $contributionsManager;

    /** @var User $user */
    private $user;

    public function let(
        Manager $manager,
        User $user,
        Contributions\Manager $contributionsManager
    ) {
        $this->beConstructedWith($manager, $contributionsManager);
        $this->manager = $manager;
        $this->contributionsManager = $contributionsManager;
        $this->user = $user;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ReferralDelegate::class);
    }

    // public function it_should_tell_manager_to_update_referral()
    // {
    //     $user = new User();
    //     $user->referrer = 123;
    //     $user->guid = 456;

    //     $referral = new Referral;
    //     $referral->setReferrerGuid($user->referrer)
    //         ->shouldBeCalled();

    //     $referral->setProspectGuid($user->guid)
    //         ->shouldBeCalled();

    //     $referral->setJoinTimestamp(time())
    //         ->shouldBeCalled();

    //     $this->manager->update($referral)
    //         ->shouldBeCalled();

    //     $this->contributionsManager->add(Argument::that(function ($contribution) {
    //         return $contribution->getMetric() === 'referrals_welcome'
    //             && $contribution->getScore() === 1
    //             && $contribution->getAmount() === 1
    //             && $contribution->getUser()->guid === 456
    //             && $contribution->getTimestamp() === strtotime('midnight') * 1000;
    //     }))
    //         ->willReturn(true);

    //     $this->onReferral($user);
    // }
}
