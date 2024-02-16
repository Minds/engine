<?php

namespace Spec\Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services;

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
