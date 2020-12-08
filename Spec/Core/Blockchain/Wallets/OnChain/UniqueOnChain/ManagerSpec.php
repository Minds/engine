<?php

namespace Spec\Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\Manager;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\Repository;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\UniqueOnChainAddress;
use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Common\Repository\Response;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Ethereum */
    protected $ethereum;

    public function let(Repository $repository, Ethereum $ethereum)
    {
        $this->beConstructedWith($repository, $ethereum);
        $this->repository = $repository;
        $this->ethereum = $ethereum;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_add_address()
    {
        $payload = json_encode(([
            'user_guid' => 123,
            'unix_ts' => time(),
        ]));
        $address = new UniqueOnChainAddress();
        $address->setAddress('0xADDR')
            ->setUserGuid('123')
            ->setPayload($payload)
            ->setSignature('0xSIG');

        //

        $this->ethereum->verifyMessage($payload, '0xSIG')
            ->willReturn('0xADDR');

        //

        $this->repository->add($address)
            ->willReturn(true);

        $this->add($address)
            ->shouldBe(true);
    }

    public function it_should_remove_address()
    {
        $address = new UniqueOnChainAddress();
        $address->setAddress('0xADDR')
            ->setUserGuid('123');

        //

        $this->repository->get('0xADDR')
            ->willReturn($address);

        $this->repository->delete($address)
            ->willReturn(true);

        $this->delete($address)
            ->shouldBe(true);
    }

    public function it_should_confirm_address_is_unique(User $user)
    {
        $user->getEthWallet()
            ->willReturn('0xADDR');

        $user->getGuid()
            ->willReturn('123');

        //

        $address = new UniqueOnChainAddress();
        $address->setAddress('0xADDR')
            ->setUserGuid('123');

        $this->repository->get('0xADDR')
            ->willReturn($address);

        $this->isUnique($user)->shouldBe(true);
    }

    public function it_should_get_address_by_string()
    {
        $address = new UniqueOnChainAddress();
        $address->setAddress('0xADDR')
            ->setUserGuid('123');

        $this->repository->get('0xADDR')
            ->willReturn($address);

        $this->getByAddress('0xADDR')->shouldBe($address);
    }
}
