<?php

namespace Spec\Minds\Core\Boost\Campaigns\Payments;

use Minds\Core\Boost\Campaigns\Payments\Payment;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PaymentSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Payment::class);
    }

    public function it_should_export()
    {
        $this->setOwnerGuid(12345)
            ->setCampaignGuid(67890)
            ->setTx('0x123abc')
            ->setSource('0x456def')
            ->setAmount(2)
            ->setTimeCreated(1570224115);

        $this->export()->shouldReturn([
            'owner_guid' => '12345',
            'campaign_guid' => '67890',
            'tx' => '0x123abc',
            'source' => '0x456def',
            'amount' => 2,
            'time_created' => 1570224115
        ]);
    }
}
