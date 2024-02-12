<?php

namespace Spec\Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services;

use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\PaywalledEntitiesRepository;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\CreatePaywalledEntityService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\Activity;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class CreatePaywalledEntityServiceSpec extends ObjectBehavior
{
    private Collaborator $paywalledEntitiesRepositoryMock;
    private Collaborator $siteMembershipReaderServiceMock;

    public function let(
        PaywalledEntitiesRepository $paywalledEntitiesRepositoryMock,
        SiteMembershipReaderService $siteMembershipReaderServiceMock
    ) {
        $this->beConstructedWith($paywalledEntitiesRepositoryMock, $siteMembershipReaderServiceMock);
        $this->paywalledEntitiesRepositoryMock = $paywalledEntitiesRepositoryMock;
        $this->siteMembershipReaderServiceMock = $siteMembershipReaderServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CreatePaywalledEntityService::class);
    }

    public function it_should_map_entity_to_memberships()
    {
        $this->siteMembershipReaderServiceMock->getSiteMemberships()
            ->willReturn([
                new SiteMembership(456, '', 0, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
                new SiteMembership(789, '', 0, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
            ]);

        $this->paywalledEntitiesRepositoryMock->mapMembershipsToEntity(Argument::any(), [ 456, 789 ])
            ->willReturn(true);

        $this->setupMemberships(new Activity(), [ 456, 789 ])
            ->shouldBe(true);
    }

    public function it_should_not_allow_invalid_membership()
    {
        $this->siteMembershipReaderServiceMock->getSiteMemberships()
            ->willReturn([
                new SiteMembership(789, '', 0, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
            ]);

        $this->paywalledEntitiesRepositoryMock->mapMembershipsToEntity(Argument::any(), [ 456, 789 ])
            ->shouldNotBeCalled();

        $this->shouldThrow(UserErrorException::class)->duringSetupMemberships(new Activity(), [ 456, 789 ]);
    }
}
