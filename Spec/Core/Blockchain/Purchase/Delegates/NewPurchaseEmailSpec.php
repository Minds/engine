<?php

namespace Spec\Minds\Core\Blockchain\Purchase\Delegates;

use Minds\Core\Blockchain\Purchase\Delegates\NewPurchaseEmail;
use Minds\Core\Blockchain\Purchase\Purchase;
use Minds\Core\Config;
use Minds\Core\Data\lookup;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NewPurchaseEmailSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NewPurchaseEmail::class);
    }

    public function it_should_send(Config $config, Custom $campaign, lookup $lookup, Purchase $purchase)
    {
        $this->beConstructedWith($config, $campaign);

        Di::_()->bind('Database\Cassandra\Data\Lookup', function ($di) use ($lookup) {
            return $lookup->getWrappedObject();
        });

        $purchase->getRequestedAmount()
            ->shouldBeCalled()
            ->willReturn(10000000000000000000);

        $purchase->getUserGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $campaign->setUser(Argument::type('Minds\Entities\User'))
            ->shouldBeCalled()
            ->willReturn($campaign);
        $campaign->setSubject('Token purchase')
            ->shouldBeCalled()
            ->willReturn($campaign);
        $campaign->setTemplate('token-purchase-new')
            ->shouldBeCalled()
            ->willReturn($campaign);
        $campaign->setTopic('billing')
            ->shouldBeCalled()
            ->willReturn($campaign);
        $campaign->setTitle('Token purchase')
            ->shouldBeCalled()
            ->willReturn($campaign);
        $campaign->setSignoff('Thank you,')
            ->shouldBeCalled()
            ->willReturn($campaign);
        $campaign->setPreheader('Your purchase of 10 Tokens is being processed.')
            ->shouldBeCalled()
            ->willReturn($campaign);
        $campaign->setCampaign('tokens')
            ->shouldBeCalled()
            ->willReturn($campaign);
        $campaign->setVars([
            'date' => date('l F jS Y', time()),
            'amount' => 10
        ])
            ->shouldBeCalled()
            ->willReturn($campaign);
        $campaign->send()
            ->shouldBeCalled();

        $this->send($purchase);
    }
}
