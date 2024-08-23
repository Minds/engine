<?php

namespace Minds\Core\MultiTenant\Models;

use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Tenant
{
    public const TRIAL_LENGTH_IN_DAYS = 14;
    public const GRACE_PERIOD_BEFORE_DELETION_IN_DAYS = 30;

    public function __construct(
        #[Field] public readonly int                        $id,
        #[Field] public readonly ?string                    $domain = null,
        #[Field(outputType: 'String')] public readonly ?int $ownerGuid = null,
        #[Field(outputType: 'String')] public readonly ?int $rootUserGuid = null,
        #[Field] public readonly ?MultiTenantConfig         $config = null,
        #[Field] public TenantPlanEnum                      $plan = TenantPlanEnum::TEAM,
        #[Field] public readonly ?int                       $trialStartTimestamp = null,
        #[Field] public ?int                                $suspendedTimestamp = null,
        public ?int                                         $deletedTimestamp = null,
        public ?string                                      $stripeSubscription = null,
    ) {
    }
}
