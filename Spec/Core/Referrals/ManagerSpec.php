<?php

namespace Spec\Minds\Core\Referrals;

use Minds\Core\Referrals\Manager;
use Minds\Core\Referrals\Referral;
use Minds\Core\Referrals\Repository;
use Minds\Core\Referrals\Delegates\NotificationDelegate;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;

use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    private $repository;
    private $notificationDelegate;
    private $entitiesBuilder;

    public function let(
        Repository $repository,
        NotificationDelegate $notificationDelegate,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($repository, $notificationDelegate, $entitiesBuilder);
        $this->repository = $repository;
        $this->notificationDelegate = $notificationDelegate;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_add_a_referral()
    {
        $referral = new Referral();
        $referral->setProspectGuid(444)
            ->setReferrerGuid(456)
            ->setRegisterTimestamp(21);
        $this->repository->add($referral)
            ->shouldBeCalled();
        $this->notificationDelegate->notifyReferrer($referral)
            ->shouldBeCalled();
        $this->add($referral)
            ->shouldReturn(true);
    }

    public function it_should_update_a_referral()
    {
        $referral = new Referral();
        $referral->setProspectGuid(555)
            ->setReferrerGuid(456)
            ->setJoinTimestamp(22);
        $this->repository->update($referral)
            ->shouldBeCalled();
        $this->notificationDelegate->notifyReferrer($referral)
            ->shouldBeCalled();
        $this->update($referral)
            ->shouldReturn(true);
    }

    public function it_should_ping_if_pingable()
    {
        $response = new Referral();

        $response->setReferrerGuid(456)
            ->setProspectGuid(123)
            ->setPingTimestamp(111)
            ->setRegisterTimestamp(11)
            ->setJoinTimestamp(null);

        $referral = new Referral();
        $referral->setProspectGuid(123)
            ->setReferrerGuid(456)
            ->setPingTimestamp(111); // Pingable because >7 days has passed since 111
        $this->repository->get($referral->getUrn())
            ->shouldBeCalled()
            ->willReturn($response);
        $this->repository->ping($referral)
            ->shouldBeCalled();
        $this->notificationDelegate->notifyProspect($referral)
            ->shouldBeCalled();
        $this->ping($referral)
            ->shouldReturn(true);
    }

    public function it_should_not_ping_during_ping_waiting_period()
    {
        $referral = new Referral();
        $referral->setProspectGuid(123)
            ->setReferrerGuid(456)
            ->setPingTimestamp(32503742874); // Jan 1, 3000; !pingable because ping timestamp is not less than now
        $this->repository->get($referral->getUrn())
            ->shouldBeCalled()
            ->willReturn(null);
        $this->repository->ping($referral)
            ->shouldNotBeCalled();
        $this->notificationDelegate->notifyProspect($referral)
            ->shouldNotBeCalled();
        $this->ping($referral)
            ->shouldReturn(false);
    }

    public function it_should_not_ping_if_current_user_is_not_referrer()
    {
        $response = new Response();
        $response[] = (new Referral)
            ->setReferrerGuid(789)
            ->setProspectGuid(123)
            ->setPingTimestamp(111)
            ->setRegisterTimestamp(11)
            ->setJoinTimestamp(null);

        $referral = new Referral();
        $referral->setProspectGuid(123)
            ->setReferrerGuid(456)
            ->setPingTimestamp(111); // Pingable because >7 days has passed since 111
        $this->repository->get($referral->getUrn())
            ->shouldBeCalled()
            ->willReturn(null);
        $this->repository->ping($referral)
            ->shouldNotBeCalled();
        $this->notificationDelegate->notifyProspect($referral)
            ->shouldNotBeCalled();
        $this->ping($referral)
            ->shouldReturn(false);
    }

    public function it_should_get_a_list_of_referrals()
    {
        $response = new Response();
        $response[] = (new Referral)
            ->setReferrerGuid(123)
            ->setProspectGuid(456)
            ->setRegisterTimestamp(11)
            ->setJoinTimestamp(22)
            ->setPingTimestamp(null);

        $this->repository->getList([
            'limit' => 12,
            'offset' => '',
            'referrer_guid' => 123,
            'hydrate' => true,
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->entitiesBuilder->single(456)
            ->shouldBeCalled()
            ->willReturn((new User)->set('guid', 456));

        $newResponse = $this->getList([
            'limit' => 12,
            'offset' => '',
            'referrer_guid' => 123,
            'hydrate' => true
        ]);

        $newResponse[0]->getReferrerGuid()
            ->shouldBe(123);
        $newResponse[0]->getProspectGuid()
            ->shouldBe(456);
        $newResponse[0]->getProspect()->getGuid()
            ->shouldBe(456);
        $newResponse[0]->getRegisterTimestamp()
            ->shouldBe(11);
        $newResponse[0]->getJoinTimestamp()
            ->shouldBe(22);
        $newResponse[0]->getPingTimestamp()
            ->shouldBe(null);
    }
}
