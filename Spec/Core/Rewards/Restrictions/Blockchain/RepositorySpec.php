<?php

namespace Spec\Minds\Core\Rewards\Restrictions\Blockchain;

use PhpSpec\ObjectBehavior;
use Minds\Core\Rewards\Restrictions\Blockchain\Repository;
use Minds\Core\Rewards\Restrictions\Blockchain\Restriction;
use Prophecy\Argument;
use Minds\Core\Data\Cassandra\Client;
use Spec\Minds\Mocks\Cassandra\Rows;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    private $client;

    public function let(
        Client $client
    ) {
        $this->client = $client;
        $this->beConstructedWith($client);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_all()
    {
        $address1 = '0x00';
        $reason1 = 'custom';
        $network1 = 'ethereum';

        $address2 = '0x01';
        $reason2 = 'ofac';
        $network2 = 'ethereum';

        $this->client->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === 'SELECT * FROM blockchain_restricted_addresses';
        }))->shouldBeCalled()
            ->willReturn(
                new Rows([
                [
                    'address' => $address1,
                    'reason' => $reason1,
                    'network' => $network1
                ],
                [
                    'address' => $address2,
                    'reason' => $reason2,
                    'network' => $network2
                ]
            ], null)
            );

        $this->getAll()->shouldBeLike([
            (new Restriction)
                ->setAddress($address1)
                ->setReason($reason1)
                ->setNetwork($network1),
            (new Restriction)
                ->setAddress($address2)
                ->setReason($reason2)
                ->setNetwork($network2)
        ]);
    }

    public function it_should_get_a_single_entry_for_a_single_address()
    {
        $address1 = '0x00';
        $reason1 = 'custom';
        $network1 = 'ethereum';

        $this->client->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === 'SELECT * FROM blockchain_restricted_addresses WHERE address = ?';
        }))->shouldBeCalled()
            ->willReturn(
                new Rows([
                [
                    'address' => $address1,
                    'reason' => $reason1,
                    'network' => $network1
                ],
            ], null)
            );

        $this->get($address1)->shouldBeLike([
            (new Restriction)
                ->setAddress($address1)
                ->setReason($reason1)
                ->setNetwork($network1),
        ]);
    }

    public function it_should_get_a_multiple_entry_for_a_single_address()
    {
        $address1 = '0x00';
        $reason1 = 'custom';
        $network1 = 'ethereum';

        $address2 = '0x01';
        $reason2 = 'ofac';
        $network2 = 'ethereum';

        $this->client->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === 'SELECT * FROM blockchain_restricted_addresses WHERE address = ?';
        }))->shouldBeCalled()
            ->willReturn(
                new Rows([
                [
                    'address' => $address1,
                    'reason' => $reason1,
                    'network' => $network1
                ],
                [
                    'address' => $address2,
                    'reason' => $reason2,
                    'network' => $network2
                ]
            ], null)
            );

        $this->get($address1)->shouldBeLike([
            (new Restriction)
                ->setAddress($address1)
                ->setReason($reason1)
                ->setNetwork($network1),
            (new Restriction)
                ->setAddress($address2)
                ->setReason($reason2)
                ->setNetwork($network2)
        ]);
    }

    public function it_should_add_a_restriction(Restriction $restriction)
    {
        $address = '0x00';
        $reason = 'custom';
        $network = 'ethereum';

        $restriction->getAddress()
            ->shouldBeCalled()
            ->willReturn($address);

        $restriction->getReason()
            ->shouldBeCalled()
            ->willReturn($reason);

        $restriction->getNetwork()
                ->shouldBeCalled()
                ->willReturn($network);

        $this->client->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === 'INSERT INTO blockchain_restricted_addresses
            (address, reason, network)
            VALUES (?, ?, ?)';
        }))->shouldBeCalled()
            ->willReturn(
                new Rows([
                [
                    'address' => $address,
                    'reason' => $reason,
                    'network' => $network
                ]
            ], null)
            );

        $this->add($restriction)->shouldBe(true);
    }

    public function it_should_delete_a_restriction()
    {
        $address = '0x00';

        $this->client->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === 'DELETE FROM blockchain_restricted_addresses WHERE address = ?';
        }))->shouldBeCalled()
            ->willReturn(
                new Rows([[]], null)
            );

        $this->delete($address)->shouldBe(true);
    }
}
