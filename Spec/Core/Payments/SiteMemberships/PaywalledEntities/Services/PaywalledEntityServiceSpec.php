<?php

namespace Spec\Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\PaywalledEntitiesRepository;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\PaywalledEntityService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\SimpleCache\CacheInterface;

class PaywalledEntityServiceSpec extends ObjectBehavior
{
    private Collaborator $paywalledEntitiesRepositoryMock;
    private Collaborator $siteMembershipReaderServiceMock;
    private Collaborator $cacheMock;

    public function let(
        PaywalledEntitiesRepository $paywalledEntitiesRepositoryMock,
        SiteMembershipReaderService $siteMembershipReaderServiceMock,
        CacheInterface $cacheMock
    ) {
        $this->beConstructedWith($paywalledEntitiesRepositoryMock, $siteMembershipReaderServiceMock, $cacheMock);
        $this->paywalledEntitiesRepositoryMock = $paywalledEntitiesRepositoryMock;
        $this->siteMembershipReaderServiceMock = $siteMembershipReaderServiceMock;
        $this->cacheMock = $cacheMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PaywalledEntityService::class);
    }

    public function it_should_return_the_cheapest_membership_if_entity_is_mapped_to_all(Activity $activityMock)
    {
        $activityMock->getGuid()
            ->willReturn('123');

        $this->paywalledEntitiesRepositoryMock->getMembershipsFromEntity(123)
            ->willReturn([-1]); // Mapped to all

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
            ->willReturn([
                new SiteMembership(3, '', 999, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
                new SiteMembership(1, '', 9, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
                new SiteMembership(2, '', 9999, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
            ]);

        $this->getLowestMembershipGuid($activityMock)
            ->shouldBe(1);
    }

    // lowestPriceSiteMembershipForActivity

    public function it_should_return_the_lowest_price_site_membership_for_activity(Activity $activityMock)
    {
        $activityMock->getGuid()
            ->willReturn('123');

        $this->paywalledEntitiesRepositoryMock->getMembershipsFromEntity(123)
            ->willReturn([2, 3]); // Mapped to multiple memberships

        $siteMembership1 = new SiteMembership(
            1,
            '',
            999,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING
        );
        $siteMembership2 = new SiteMembership(
            2,
            '',
            1999,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING
        );
        $siteMembership3 = new SiteMembership(
            3,
            '',
            499,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING
        );

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
            ->willReturn([
                $siteMembership1,
                $siteMembership2,
                $siteMembership3,
            ]);

        $this->lowestPriceSiteMembershipForActivity($activityMock)
            ->shouldBe($siteMembership3);
    }

    public function it_should_return_null_when_no_matching_site_membership_found(Activity $activityMock)
    {
        $activityMock->getGuid()
            ->willReturn('123');

        $this->paywalledEntitiesRepositoryMock->getMembershipsFromEntity(123)
            ->willReturn([4]); // Membership guid that doesn't exist in site memberships

        $siteMembership1 = new SiteMembership(
            1,
            '',
            999,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING
        );
        $siteMembership2 = new SiteMembership(
            2,
            '',
            1999,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING
        );
        $siteMembership3 = new SiteMembership(
            3,
            '',
            499,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING
        );

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
            ->willReturn([
                $siteMembership1,
                $siteMembership2,
                $siteMembership3,
            ]);

        $this->lowestPriceSiteMembershipForActivity($activityMock)
            ->shouldBeNull();
    }

    public function it_should_return_null_when_no_site_membership_found_for_activity(Activity $activityMock)
    {
        $activityMock->getGuid()
            ->willReturn('123');

        $this->paywalledEntitiesRepositoryMock->getMembershipsFromEntity(123)
            ->willReturn(null);

        $this->lowestPriceSiteMembershipForActivity($activityMock)
            ->shouldBeNull();
    }

    public function it_should_return_null_when_onlyExternal_is_true_and_only_internal_memberships_are_found(Activity $activityMock)
    {
        $activityMock->getGuid()
            ->willReturn(123);

        $this->paywalledEntitiesRepositoryMock->getMembershipsFromEntity(123)
            ->willReturn([1, 2]);

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
            ->willReturn([
                new SiteMembership(
                    1,
                    '',
                    999,
                    SiteMembershipBillingPeriodEnum::MONTHLY,
                    SiteMembershipPricingModelEnum::RECURRING,
                    null,
                    null,
                    'USD',
                    null,
                    null,
                    false,
                    false // onlyExternal
                ),
                new SiteMembership(
                    2,
                    '',
                    1999,
                    SiteMembershipBillingPeriodEnum::MONTHLY,
                    SiteMembershipPricingModelEnum::RECURRING,
                    null,
                    null,
                    'USD',
                    null,
                    null,
                    false,
                    false // onlyExternal
                ),
            ]);

        $this->lowestPriceSiteMembershipForActivity($activityMock, true)
            ->shouldBeNull();
    }

    public function it_should_return_the_lowest_priced_external_membership_when_onlyExternal_is_true(Activity $activityMock)
    {
        $activityMock->getGuid()
            ->willReturn(123);

        $this->paywalledEntitiesRepositoryMock->getMembershipsFromEntity(123)
            ->willReturn([1, 2, 3]);

        $lowestPricedExternalMembership = new SiteMembership(
            2,
            '',
            999,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING,
            null,
            null,
            'USD',
            null,
            null,
            false,
            true // onlyExternal
        );

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
            ->willReturn([
                new SiteMembership(
                    1,
                    '',
                    499,
                    SiteMembershipBillingPeriodEnum::MONTHLY,
                    SiteMembershipPricingModelEnum::RECURRING,
                    null,
                    null,
                    'USD',
                    null,
                    null,
                    false,
                    false // onlyExternal
                ),
                $lowestPricedExternalMembership,
                new SiteMembership(
                    3,
                    '',
                    1999,
                    SiteMembershipBillingPeriodEnum::MONTHLY,
                    SiteMembershipPricingModelEnum::RECURRING,
                    null,
                    null,
                    'USD',
                    null,
                    null,
                    false,
                    true // onlyExternal
                ),
            ]);

        $this->lowestPriceSiteMembershipForActivity($activityMock, true)
            ->shouldReturn($lowestPricedExternalMembership);
    }

    //

    public function it_should_return_the_cheapest_membership_if_entity_is_mapped_to_multiple(Activity $activityMock)
    {
        $activityMock->getGuid()
            ->willReturn('123');

        $this->paywalledEntitiesRepositoryMock->getMembershipsFromEntity(123)
            ->willReturn([1,2,3]); // Mapped to multiple

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
            ->willReturn([
                new SiteMembership(3, '', 999, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
                new SiteMembership(1, '', 9, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
                new SiteMembership(2, '', 9999, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
            ]);

        $this->getLowestMembershipGuid($activityMock)
            ->shouldBe(1);
    }

    public function it_should_return_the_exact_membership_if_mapped_to_one(Activity $activityMock)
    {
        $activityMock->getGuid()
            ->willReturn('123');

        $this->paywalledEntitiesRepositoryMock->getMembershipsFromEntity(123)
            ->willReturn([3]); // Mapped to multiple

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
            ->willReturn([
                new SiteMembership(3, '', 999, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
            ]);

        $this->getLowestMembershipGuid($activityMock)
            ->shouldBe(3);
    }
}
