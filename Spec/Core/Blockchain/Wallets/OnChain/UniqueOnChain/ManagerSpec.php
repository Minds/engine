<?php

namespace Spec\Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Google\Cloud\BigQuery\Numeric;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\Manager;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\Repository;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\UniqueOnChainAddress;
use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Blockchain\BigQuery\HoldersQuery;
use Minds\Entities\User;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Ethereum */
    protected $ethereum;

    /** @var HoldersQuery */
    protected $holdersQuery;

    public function let(
        Repository $repository,
        Ethereum $ethereum,
        HoldersQuery $holdersQuery,
    ) {
        $this->beConstructedWith(
            $repository,
            $ethereum,
            $holdersQuery,
        );
        $this->repository = $repository;
        $this->ethereum = $ethereum;
        $this->holdersQuery = $holdersQuery;
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

    public function it_should_get_all_via_bigquery(
        UniqueOnChainAddress $address1,
        UniqueOnChainAddress $address2,
        UniqueOnChainAddress $address3
    ) {
        $this->holdersQuery->get()
            ->shouldBeCalled()
            ->willReturn([
                [
                    'addr' => '0x1',
                    'balance' => new Numeric('0.1')
                ],
                [
                    'addr' => '0x2',
                    'balance' => new Numeric('0.2')
                ],
                [
                    'addr' => '0x3',
                    'balance' => new Numeric('0.3')
                ]
            ]);

        $this->repository->get('0x1')
            ->shouldBeCalled()
            ->willReturn($address1);

        $this->repository->get('0x2')
            ->shouldBeCalled()
            ->willReturn($address2);

        $this->repository->get('0x3')
            ->shouldBeCalled()
            ->willReturn($address3);

        $this->getAll()->shouldBeAGenerator([
            $address1,
            $address2,
            $address3
        ]);
    }

    public function it_should_get_all_via_bigquery_without_balanceless_addresses(
        UniqueOnChainAddress $address1,
        UniqueOnChainAddress $address3
    ) {
        $this->holdersQuery->get()
            ->shouldBeCalled()
            ->willReturn([
                [
                    'addr' => '0x1',
                    'balance' => new Numeric('0.1')
                ],
                [
                    'addr' => '0x2',
                    'balance' => new Numeric('0.0')
                ],
                [
                    'addr' => '0x3',
                    'balance' => new Numeric('0.3')
                ]
            ]);

        $this->repository->get('0x1')
            ->shouldBeCalled()
            ->willReturn($address1);

        $this->repository->get('0x2')
            ->shouldNotBeCalled();

        $this->repository->get('0x3')
            ->shouldBeCalled()
            ->willReturn($address3);

        $this->getAll()->shouldBeAGenerator([
            $address1,
            $address3
        ]);
    }

    public function it_should_get_all_via_bigquery_without_addresses_not_in_our_system(
        UniqueOnChainAddress $address1,
        UniqueOnChainAddress $address3
    ) {
        $this->holdersQuery->get()
            ->shouldBeCalled()
            ->willReturn([
                [
                    'addr' => '0x1',
                    'balance' => new Numeric('0.1')
                ],
                [
                    'addr' => '0x2',
                    'balance' => new Numeric('0.2')
                ],
                [
                    'addr' => '0x3',
                    'balance' => new Numeric('0.3')
                ]
            ]);

        $this->repository->get('0x1')
            ->shouldBeCalled()
            ->willReturn($address1);

        $this->repository->get('0x2')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->get('0x3')
            ->shouldBeCalled()
            ->willReturn($address3);

        $this->getAll()->shouldBeAGenerator([
            $address1,
            $address3
        ]);
    }

    public function getMatchers(): array
    {
        $matchers = [];

        $matchers['beAGenerator'] = function ($subject, $items) {
            $subjectItems = iterator_to_array($subject);

            if ($subjectItems !== $items) {
                throw new FailureException(sprintf("Subject should be a traversable containing %s, but got %s.", json_encode($items), json_encode($subjectItems)));
            }

            return true;
        };

        return $matchers;
    }
}
