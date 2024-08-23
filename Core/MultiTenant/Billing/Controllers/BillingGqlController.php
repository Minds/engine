<?php
namespace Minds\Core\MultiTenant\Billing\Controllers;

use Minds\Core\MultiTenant\Billing\BillingService;
use Minds\Core\MultiTenant\Billing\Types\TenantBillingType;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class BillingGqlController
{
    public function __construct(
        private readonly BillingService $service,
    ) {
        
    }
    
    #[Query]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function getTenantBilling(#[InjectUser] ?User $loggedInUser = null): TenantBillingType
    {
        return $this->service->getTenantBillingOverview();
    }
}
