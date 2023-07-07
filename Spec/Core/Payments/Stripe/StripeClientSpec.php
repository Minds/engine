<?php

namespace Spec\Minds\Core\Payments\Stripe;

use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Payments\Stripe\StripeClient;
use PhpSpec\ObjectBehavior;

class StripeClientSpec extends ObjectBehavior
{
    /** @var StripeApiKeyConfig */
    private $stripeApiKeyConfig;

    public function let(StripeApiKeyConfig $stripeApiKeyConfig)
    {
        $this->stripeApiKeyConfig = $stripeApiKeyConfig;

        $this->stripeApiKeyConfig->get()
            ->shouldBeCalled()
            ->willReturn('~key~');

        $this->beConstructedWith(null, $stripeApiKeyConfig);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StripeClient::class);
    }
}
