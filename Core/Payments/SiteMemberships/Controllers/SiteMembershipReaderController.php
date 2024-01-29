<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\Group;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;

class SiteMembershipReaderController
{
    public function __construct(
        private readonly SiteMembershipReaderService $siteMembershipReaderService
    ) {
    }

    /**
     * @param User $loggedInUser
     * @return SiteMembership[]
     */
    #[Query]
    #[Logged]
    public function siteMemberships(
        #[InjectUser] User $loggedInUser
    ): array {
        return [
            new SiteMembership(
                membershipGuid: 1,
                membershipName: 'Free',
                membershipDescription: 'Free',
                membershipPriceInCents: 1000, // $10.00
                priceCurrency: 'USD',
                membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY,
                membershipPricingModel: SiteMembershipPricingModelEnum::RECURRING,
                roles: [
                    1,
                    2
                ],
                groups: [
                    new GroupNode(
                        (new Group())
                            ->loadFromArray([
                                'guid' => 1,
                                'name' => 'Test Group 1',
                            ])
                    ),
                    new GroupNode(
                        (new Group())
                            ->loadFromArray([
                                'guid' => 2,
                                'name' => 'Test Group 2',
                            ])
                    ),
                ]
            ),
            new SiteMembership(
                membershipGuid: 2,
                membershipName: "Premium",
                membershipDescription: "*Membership* description from [stripe](https://www.stripe.com)

- Benefit 1
- Benefit 2",
                membershipPriceInCents: 1500, // $10.00
                priceCurrency: 'USD',
                membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY,
                membershipPricingModel: SiteMembershipPricingModelEnum::ONE_TIME,
                roles: [
                    1,
                    2
                ],
            ),
            new SiteMembership(
                membershipGuid: 3,
                membershipName: "Premium 2",
                membershipDescription: "*Membership* description from [stripe](https://www.stripe.com)

- Benefit 1
- Benefit 2",
                membershipPriceInCents: 2000, // $10.00
                priceCurrency: 'USD',
                membershipBillingPeriod: SiteMembershipBillingPeriodEnum::YEARLY,
                membershipPricingModel: SiteMembershipPricingModelEnum::ONE_TIME,
                roles: [
                    1,
                    2
                ],
            ),
        ];
    }

    // public function siteMembership(): SiteMembership
    // {
    //
    // }
}
