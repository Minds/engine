<?php

namespace Spec\Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\Repository;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\UniqueOnChainAddress;
use Minds\Core\Data\Cassandra;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var Cassandra\Client */
    protected $db;

    public function let(Cassandra\Client $db)
    {
        $this->beConstructedWith($db);
        $this->db = $db;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_address()
    {
        $this->db->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->willReturn([
                [
                    'address' => '0xADDR',
                    'user_guid' => 123
                ]
            ]);
        //

        $address = $this->get('0xADDR');
        $address->getAddress()->shouldBe('0xADDR');
        $address->getUserGuid()->shouldBe('123');
    }

    public function it_should_return_list()
    {
        $this->db->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->willReturn([
                [
                    'address' => '0xADDR',
                    'user_guid' => 123
                ],
                [
                    'address' => '0xADDR_1',
                    'user_guid' => 456
                ]
            ]);
        //

        $addresses = $this->getList([]);
        $addresses[0]->getAddress()->shouldBe('0xADDR');
        $addresses[0]->getUserGuid()->shouldBe('123');
        $addresses[1]->getAddress()->shouldBe('0xADDR_1');
        $addresses[1]->getUserGuid()->shouldBe('456');
    }

    public function it_should_add()
    {
        $this->db->request(Argument::that(function ($prepared) {
            $query = $prepared->build();
            return $query['values'][0] === '0xaddr';
        }))
            ->willReturn(true);

        //

        $address = new UniqueOnChainAddress();
        $address->setAddress('0xADDR');

        $this->add($address)
            ->shouldBe(true);
    }

    public function it_should_delete()
    {
        $this->db->request(Argument::that(function ($prepared) {
            $query = $prepared->build();
            return $query['values'][0] === '0xaddr';
        }))
            ->willReturn(true);

        $address = new UniqueOnChainAddress();
        $address->setAddress('0xADDR');

        $this->delete($address)
            ->shouldBe(true);
    }
}
