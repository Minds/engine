<?php

namespace Spec\Minds\Core\MultiTenant\Billing\Controllers;

use Minds\Core\MultiTenant\Billing\Controllers\BillingPsrController;
use Minds\Core\MultiTenant\Billing\BillingService;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

class BillingPsrControllerSpec extends ObjectBehavior
{
    private Collaborator $billingServiceMock;
    
    public function let(BillingService $billingServiceMock)
    {
        $this->billingServiceMock = $billingServiceMock;
        $this->beConstructedWith($billingServiceMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BillingPsrController::class);
    }

    // externalTrialCheckout

    public function it_should_handle_external_trial_checkout_without_customer_url(
        ServerRequestInterface $requestMock
    ) {
        $stripeUrl = 'https://stripe.com/path';

        $requestMock->getQueryParams()->willReturn([]);

        $this->billingServiceMock->createExternalTrialCheckoutLink(
            plan: TenantPlanEnum::TEAM,
            timePeriod: CheckoutTimePeriodEnum::MONTHLY,
            customerUrl: null
        )->willReturn($stripeUrl);

        $response = $this->externalTrialCheckout($requestMock);

        $response->shouldBeAnInstanceOf(RedirectResponse::class);
        $response->getStatusCode()->shouldBe(302);
        $response->getHeader('Location')->shouldBeLike([$stripeUrl]);
    }

    public function it_should_handle_external_trial_checkout_with_customer_url(
        ServerRequestInterface $requestMock
    ) {
        $customerUrl = 'https://example.minds.com/';
        $stripeUrl = 'https://stripe.com/path';

        $requestMock->getQueryParams()->willReturn([
            'customer_url' => $customerUrl
        ]);

        $this->billingServiceMock->createExternalTrialCheckoutLink(
            plan: TenantPlanEnum::TEAM,
            timePeriod: CheckoutTimePeriodEnum::MONTHLY,
            customerUrl: $customerUrl
        )->willReturn($stripeUrl);

        $response = $this->externalTrialCheckout($requestMock);

        $response->shouldBeAnInstanceOf(RedirectResponse::class);
        $response->getStatusCode()->shouldBe(302);
        $response->getHeader('Location')->shouldBeLike([$stripeUrl]);
    }
}
