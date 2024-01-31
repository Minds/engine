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
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
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
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     */
    #[Query]
    public function siteMemberships(
        #[InjectUser] User $loggedInUser
    ): array {
        return $this->siteMembershipReaderService->getSiteMemberships();
        //         return [
        //             new SiteMembership(
        //                 membershipGuid: 1,
        //                 membershipName: 'Free',
        //                 membershipPriceInCents: 1000,
        //                 membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY, // $10.00
        //                 membershipPricingModel: SiteMembershipPricingModelEnum::RECURRING,
        //                 membershipDescription: 'Free',
        //                 priceCurrency: 'USD',
        //                 roles: [
        //                     1,
        //                     2
        //                 ],
        //                 groups: [
        //                     new GroupNode(
        //                         (new Group())
        //                             ->loadFromArray([
        //                                 'guid' => 1,
        //                                 'name' => 'Test Group 1',
        //                             ])
        //                     ),
        //                     new GroupNode(
        //                         (new Group())
        //                             ->loadFromArray([
        //                                 'guid' => 2,
        //                                 'name' => 'Test Group 2',
        //                             ])
        //                     ),
        //                 ]
        //             ),
        //             new SiteMembership(
        //                 membershipGuid: 2,
        //                 membershipName: "Premium",
        //                 membershipPriceInCents: 1500,
        //                 membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY, // $10.00
        //                 membershipPricingModel: SiteMembershipPricingModelEnum::ONE_TIME,
        //                 membershipDescription: "*Membership* description from [stripe](https://www.stripe.com)
        //
        // - Benefit 1
        // - Benefit 2",
        //                 priceCurrency: 'USD',
        //                 roles: [
        //                     1,
        //                     2
        //                 ],
        //             ),
        //             new SiteMembership(
        //                 membershipGuid: 3,
        //                 membershipName: "Premium 2",
        //                 membershipPriceInCents: 2000,
        //                 membershipBillingPeriod: SiteMembershipBillingPeriodEnum::YEARLY, // $10.00
        //                 membershipPricingModel: SiteMembershipPricingModelEnum::ONE_TIME,
        //                 membershipDescription: "*Membership* description from [stripe](https://www.stripe.com)
        //
        // - Benefit 1
        // - Benefit 2",
        //                 priceCurrency: 'USD',
        //                 groups: [
        //                     new GroupNode(
        //                         (new Group())
        //                             ->loadFromArray([
        //                                 'guid' => 1,
        //                                 'name' => 'Test Group 1',
        //                             ])
        //                     ),
        //                     new GroupNode(
        //                         (new Group())
        //                             ->loadFromArray([
        //                                 'guid' => 2,
        //                                 'name' => 'Test Group 2',
        //                             ])
        //                     ),
        //                 ]
        //             ),
        //         ];
    }

    #[Query]
    public function siteMembership(
        string $membershipGuid
    ): SiteMembership {
        // TODO: Implement getSiteMembership() method.
        return new SiteMembership(
            membershipGuid: (int)$membershipGuid,
            membershipName: 'Free',
            membershipPriceInCents: 1000, // $10.00
            membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY, // $10.00
            membershipPricingModel: SiteMembershipPricingModelEnum::RECURRING,
            membershipDescription: 'Free',
            priceCurrency: 'USD',
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
        );
    }
}
