<?php

namespace Spec\Minds\Core\Channels\Delegates;

use Minds\Core\Channels\Delegates\CancelPaidPlans;
use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CancelPaidPlansSpec extends ObjectBehavior
{
    public function let(StripeClient $stripeClient, Config $config)
    {
        $this->beConstructedWith($stripeClient, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CancelPaidPlans::class);
    }

    public function it_does_nothing_when_no_plan_products_are_configured(
        StripeClient $stripeClient,
        Config $config,
        User $user
    ) {
        $user->getGuid()->willReturn('1000');
        $config->get('upgrades')->willReturn(['plus' => [], 'pro' => []]);

        $stripeClient->withUser(Argument::any())->shouldNotBeCalled();

        $this->cancel($user)->shouldReturn([]);
    }

    public function it_does_nothing_for_an_invalid_guid(
        StripeClient $stripeClient,
        User $user
    ) {
        $user->getGuid()->willReturn("1' OR '1");

        $stripeClient->withUser(Argument::any())->shouldNotBeCalled();

        $this->cancel($user)->shouldReturn([]);
    }
}
