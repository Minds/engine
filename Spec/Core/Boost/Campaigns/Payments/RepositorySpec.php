<?php

namespace Spec\Minds\Core\Boost\Campaigns\Payments;

use Cassandra\Bigint;
use Cassandra\Decimal;
use Cassandra\Timestamp;
use Minds\Common\Repository\Response;
use Minds\Core\Boost\Campaigns\Payments\Payment;
use Minds\Core\Boost\Campaigns\Payments\Repository;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var CassandraClient */
    protected $db;

    public function let(CassandraClient $db)
    {
        $this->beConstructedWith($db);
        $this->db = $db;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_return_a_list_of_payments()
    {
        $paymentsData = [
            [
                'owner_guid' => new Bigint('12345'),
                'campaign_guid' => new Bigint('67890'),
                'tx' => '0x123abc',
                'source' => '0x456def',
                'amount' => new Decimal(2),
                'time_created' => new Timestamp(1570224115)
            ]
        ];

        $this->db->request(Argument::type(Custom::class))->shouldBeCalled()->willReturn($paymentsData);
        $this->getList([
            'owner_guid' => 12345,
            'campaign_guid' => 67890,
            'tx' => '0x123abc'
        ])[0]->shouldBeAnInstanceOf(Payment::class);
    }

    public function it_should_add_a_payment(Payment $payment)
    {
        $payment->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $payment->getCampaignGuid()->shouldBeCalled()->willReturn(67890);
        $payment->getTx()->shouldBeCalled()->willReturn('0x123abc');
        $payment->getSource()->shouldBeCalled()->willReturn('0x456def');
        $payment->getAmount()->shouldBeCalled()->willReturn(2);
        $payment->getTimeCreated()->shouldBeCalled()->willReturn(1570224115);

        $this->db->request(Argument::type(Custom::class), true)->shouldBeCalled()->willReturn(true);

        $this->add($payment)->shouldReturn(true);
    }
}
