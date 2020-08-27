<?php

namespace Spec\Minds\Core\Blockchain\Purchase\Delegates;

use Minds\Core\Blockchain\Purchase\Delegates\IssuedTokenEmail;
use Minds\Core\Blockchain\Purchase\Purchase;
use Minds\Core\Config;
use Minds\Core\Data\lookup;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class IssuedTokenEmailSpec extends ObjectBehavior
{
    /** @var Custom */
    protected $campaign;

    public function let(Config $config, Custom $campaign, lookup $lookup)
    {
        $this->campaign = $campaign;

        $this->beConstructedWith($config, $campaign);

        Di::_()->bind('Database\Cassandra\Data\Lookup', function ($di) use ($lookup) {
            return $lookup->getWrappedObject();
        });
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(IssuedTokenEmail::class);
    }

    public function it_should_send(Purchase $purchase)
    {
        $purchase->getRequestedAmount()
            ->shouldBeCalled()
            ->willReturn(10000000000000000000);

        $purchase->getUserGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->campaign->setUser(Argument::type('Minds\Entities\User'))
            ->shouldBeCalled()
            ->willReturn($this->campaign);
        $this->campaign->setSubject('Thank you for your purchase')
            ->shouldBeCalled()
            ->willReturn($this->campaign);
        $this->campaign->setTemplate('token-purchase-issued')
            ->shouldBeCalled()
            ->willReturn($this->campaign);
        $this->campaign->setTopic('billing')
            ->shouldBeCalled()
            ->willReturn($this->campaign);
        $this->campaign->setTitle('Thank you for your purchase')
            ->shouldBeCalled()
            ->willReturn($this->campaign);
        $this->campaign->setSignoff('Thank you,')
            ->shouldBeCalled()
            ->willReturn($this->campaign);
        $this->campaign->setPreheader('Your purchase of 10 Tokens has now been issued.')
            ->shouldBeCalled()
            ->willReturn($this->campaign);
        $this->campaign->setCampaign('tokens')
            ->shouldBeCalled()
            ->willReturn($this->campaign);
        $this->campaign->setVars([
            'date' => date('l F jS Y', time()),
            'amount' => 10,
        ])
            ->shouldBeCalled()
            ->willReturn($this->campaign);
        $this->campaign->send()
            ->shouldBeCalled();

        $this->send($purchase);
    }
}
