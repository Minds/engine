<?php

namespace Spec\Minds\Core\MultiTenant\Billing;

use Minds\Core\MultiTenant\Billing\BillingPsrController;
use Minds\Core\MultiTenant\Billing\BillingService;
use PhpSpec\ObjectBehavior;

class BillingPsrControllerSpec extends ObjectBehavior
{
    public function let(BillingService $billingServiceMock)
    {
        $this->beConstructedWith($billingServiceMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BillingPsrController::class);
    }
}
