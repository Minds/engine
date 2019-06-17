<?php

namespace Spec\Minds\Core\Referrals;

use Minds\Core\Referrals\Manager;
use Minds\Core\Referrals\Repository;
use Minds\Core\Referrals\Referral;
use Minds\Core;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $repository;

    function let(Repository $repository) 
    {
        $this->beConstructedWith($repository);
        $this->repository=$repository;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    function it_should_pass_referral_to_repository()
    {
        $referral = new Referral();
        $referral->setProspectGuid(Core\Session::getLoggedInUserGuid())
            ->setReferrerGuid('1234')
            ->setRegisterTimestamp(time());
        $this->repository->add($referral)
            ->shouldBeCalled();
        $this->add('123')
            ->shouldReturn(true);
    }
}
