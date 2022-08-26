<?php

namespace Spec\Minds\Core\Rewards\Restrictions\Blockchain;

use PhpSpec\ObjectBehavior;
use Minds\Core\Rewards\Restrictions\Blockchain\Manager;
use Minds\Core\Rewards\Restrictions\Blockchain\Repository;
use Minds\Core\Channels\Ban;
use Minds\Core\Rewards\Restrictions\Blockchain\RestrictedException;
use Minds\Core\Rewards\Restrictions\Blockchain\Restriction;
use Minds\Entities\User;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    private $repository;

    /** @var Ban */
    private $banManager;


    public function let(
        Repository $repository,
        Ban $banManager
    ) {
        $this->repository = $repository;
        $this->banManager = $banManager;
        $this->beConstructedWith($repository, $banManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_call_to_get_all()
    {
        $arr = [new Restriction(), new Restriction(), new Restriction()];

        $this->repository->getAll()
            ->shouldBeCalled()
            ->willReturn($arr);

        $this->getAll()->shouldBe($arr);
    }

    public function it_should_get_a_single_restriction()
    {
        $address = '0x00';
        $restrictions = [ new Restriction() ];

        $this->repository->get($address)
            ->shouldBeCalled()
            ->willReturn($restrictions);

        $this->get($address)->shouldBe($restrictions);
    }

    public function it_should_add_a_restriction()
    {
        $restriction = new Restriction();
        
        $this->repository->add($restriction)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($restriction)->shouldBe(true);
    }

    public function it_should_delete_a_restriction()
    {
        $address = '0x00';

        $this->repository->delete($address)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->delete($address)->shouldBe(true);
    }

    public function it_should_determine_if_a_user_is_restricted()
    {
        $address = '0x00';
        $restrictions = [new Restriction()];

        $this->repository->get($address)
            ->shouldBeCalled()
            ->willReturn($restrictions);

        $this->isRestricted($address)->shouldBe(true);
    }

    public function it_should_determine_if_a_user_is_NOT_restricted() {
        $address = '0x00';
        $restrictions = [];

        $this->repository->get($address)
            ->shouldBeCalled()
            ->willReturn($restrictions);

        $this->isRestricted($address)->shouldBe(false);
    }

    public function it_should_check_if_user_is_restricted_and_ban(User $user) {
        $address = '0x00';
        $restrictions = [new Restriction()];

        $this->repository->get($address)
            ->shouldBeCalled()
            ->willReturn($restrictions);

        $this->banManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->banManager);

        $this->banManager->ban(11)
            ->shouldBeCalled();

        $this->shouldThrow(RestrictedException::class)->during('gatekeeper', [$address, $user]);
    }

    public function it_should_check_if_user_is_NOT_restricted_and_ban(User $user) {
        $address = '0x00';
        $restrictions = [];

        $this->repository->get($address)
            ->shouldBeCalled()
            ->willReturn($restrictions);

        $this->banManager->ban(Argument::any())
            ->shouldNotBeCalled();

        $this->gatekeeper($address, $user)->shouldBe(null);   
    }
}
