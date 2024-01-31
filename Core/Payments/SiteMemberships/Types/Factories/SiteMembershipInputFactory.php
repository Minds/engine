<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Types\Factories;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Core\Guid;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use TheCodingMachine\GraphQLite\Annotations\Factory;

class SiteMembershipInputFactory
{
    public function __construct(
        private readonly EntitiesBuilder $entitiesBuilder
    ) {
    }

    /**
     * @param string $membershipName
     * @param float $membershipPrice
     * @param SiteMembershipBillingPeriodEnum $membershipBillingPeriod
     * @param SiteMembershipPricingModelEnum $membershipPricingModel
     * @param string|null $membershipDescription
     * @param int[]|null $roles
     * @param string[]|null $groups
     * @return SiteMembership
     */
    #[Factory(name: 'SiteMembershipInput')]
    public function createSiteMembership(
        string                          $membershipName,
        float                           $membershipPrice,
        SiteMembershipBillingPeriodEnum $membershipBillingPeriod,
        SiteMembershipPricingModelEnum  $membershipPricingModel,
        ?string                         $membershipDescription = null,
        ?array                          $roles = null,
        ?array                          $groups = null,
    ): SiteMembership {
        return new SiteMembership(
            membershipGuid: (int)Guid::build(),
            membershipName: $membershipName,
            membershipPriceInCents: (int)($membershipPrice * 100),
            membershipBillingPeriod: $membershipBillingPeriod,
            membershipPricingModel: $membershipPricingModel,
            membershipDescription: $membershipDescription,
            roles: $roles,
            groups: $groups ? $this->processGroups($groups) : null
        );
    }

    private function processGroups(array $groups): array
    {
        $processedGroups = [];

        foreach ($groups as $groupGuid) {
            if ($group = $this->entitiesBuilder->single($groupGuid)) {
                $processedGroups[] = new GroupNode($group);
            }
        }

        return $processedGroups;
    }
}
