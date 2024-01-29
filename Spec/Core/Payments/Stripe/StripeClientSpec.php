<?php

namespace Spec\Minds\Core\Payments\Stripe;

use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Payments\Stripe\StripeClient;
use PhpSpec\ObjectBehavior;
use Stripe\Exception\AuthenticationException;

class StripeClientSpec extends ObjectBehavior
{
    /** @var StripeApiKeyConfig */
    private $stripeApiKeyConfig;

    public function let(StripeApiKeyConfig $stripeApiKeyConfig)
    {
        $this->stripeApiKeyConfig = $stripeApiKeyConfig;

        $this->beConstructedWith(null, $stripeApiKeyConfig);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StripeClient::class);
    }

    public function it_should_call_stripe_api_client()
    {
        $this->stripeApiKeyConfig->get()
            ->shouldBeCalled()
            ->willReturn('~key~');

        $this->accounts->shouldThrow(AuthenticationException::class)->duringAll();
    }
}
