<?php

namespace Spec\Minds\Core\Wire\SupportTiers;

use Minds\Core\Wire\SupportTiers\Manager;
use Minds\Core\Wire\SupportTiers\Repository;
use Minds\Core\Wire\SupportTiers\SupportTier;
use Minds\Entities\User;
use Minds\Core\Wire\Wire;
use Minds\Common\Repository\Response;
use Minds\Core\Wire\SupportTiers\Delegates\PaymentsDelegate;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    private Collaborator $paymentsDelegateMock;

    public function let(Repository $repository, PaymentsDelegate $paymentsDelegateMock)
    {
        $this->beConstructedWith($repository, null, null, $paymentsDelegateMock);
        $this->repository = $repository;
        $this->paymentsDelegateMock = $paymentsDelegateMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_support_tier_by_wire(Wire $wire, User $receiver)
    {
        $receiver->get('guid')->willReturn('456');
        $wire->getReceiver()->willReturn($receiver);
        $wire->isRecurring()->willReturn(true);
        $wire->getAmount()->willReturn(100);
        $wire->getMethod()->willReturn('usd');
        
        $t1 = (new SupportTier)
            ->setPublic(true)
            ->setHasUsd(true)
            ->setUsd(1);
    
        $this->repository->getList(Argument::any())
            ->willReturn(new Response([
                $t1
            ]));

        $this->paymentsDelegateMock->hydrate($t1)->willReturn($t1);

        $supportTier = $this->getByWire($wire);
        $supportTier->getUsd()->shouldBe(1);
    }

    public function it_should_get_support_tier_by_token_wire(Wire $wire, User $receiver)
    {
        $receiver->get('guid')->willReturn('456');
        $wire->getReceiver()->willReturn($receiver);
        $wire->isRecurring()->willReturn(true);
        $wire->getAmount()->willReturn("800000000000000000");
        $wire->getMethod()->willReturn('tokens');
        
        $t1 = (new SupportTier)
            ->setPublic(true)
            ->setHasTokens(true)
            ->setUsd(1);

        $this->repository->getList(Argument::any())
            ->willReturn(new Response([
                $t1
            ]));

        $this->paymentsDelegateMock->hydrate($t1)->willReturn($t1);

        $supportTier = $this->getByWire($wire);
        $supportTier->getTokens()->shouldBe(0.8);
    }
}
