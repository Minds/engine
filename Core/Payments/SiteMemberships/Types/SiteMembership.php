<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Types;

use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Models\Role;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class SiteMembership
{
    public function __construct(
        #[Field(outputType: "String!")] public readonly int      $membershipGuid,
        #[Field] public readonly string                          $membershipName,
        #[Field] public readonly int                             $membershipPriceInCents,
        #[Field] public readonly SiteMembershipBillingPeriodEnum $membershipBillingPeriod,
        #[Field] public readonly SiteMembershipPricingModelEnum  $membershipPricingModel,
        #[Field] public readonly ?string                         $membershipDescription = null,
        #[Field] public readonly string                          $priceCurrency = 'USD',
        private readonly ?array                                  $roles = null,
        private readonly ?array                                  $groups = null
    ) {
    }

    /**
     * @return Role[]|null
     */
    #[Field]
    public function getRoles(): ?array
    {
        if (!$this->roles) {
            return null;
        }

        $roles = [];
        foreach ($this->roles as $roleId) {
            $roleEnum = RolesEnum::from($roleId);
            $roles[$roleEnum->value] = new Role(
                id: $roleEnum->value,
                name: $roleEnum->name,
                permissions: []
            );
        }
        return $roles;
    }

    /**
     * @return GroupNode[]|null
     */
    #[Field]
    public function getGroups(): ?array
    {
        return $this->groups;
    }
}
