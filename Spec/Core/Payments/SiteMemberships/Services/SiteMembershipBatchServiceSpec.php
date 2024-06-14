<?php

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use DateTimeImmutable;
use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Repositories\TenantUsersRepository;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBatchIdTypeEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Repositories\DTO\SiteMembershipSubscriptionDTO;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipBatchService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipBatchUpdate;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class SiteMembershipBatchServiceSpec extends ObjectBehavior
{
    private Collaborator $entitiesBuilderMock;
    private Collaborator $oidcUserServiceMock;
    private Collaborator $tenantUsersRepositoryMock;
    private Collaborator $configMock;
    private Collaborator $readerServiceMock;
    private Collaborator $siteMembershipSubscriptionsServiceMock;

    public function let(
        EntitiesBuilder $entitiesBuilderMock,
        OidcUserService $oidcUserServiceMock,
        TenantUsersRepository $tenantUsersRepositoryMock,
        Config $configMock,
        SiteMembershipReaderService $readerServiceMock,
        SiteMembershipSubscriptionsService $siteMembershipSubscriptionsServiceMock,
        Logger $loggerMock,
    ) {
        $this->beConstructedWith($entitiesBuilderMock, $oidcUserServiceMock, $tenantUsersRepositoryMock, $configMock, $readerServiceMock, $siteMembershipSubscriptionsServiceMock, $loggerMock);
        
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->oidcUserServiceMock = $oidcUserServiceMock;
        $this->tenantUsersRepositoryMock = $tenantUsersRepositoryMock;
        $this->configMock = $configMock;

        $this->readerServiceMock = $readerServiceMock;
        $this->siteMembershipSubscriptionsServiceMock = $siteMembershipSubscriptionsServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SiteMembershipBatchService::class);
    }

    public function it_should_not_allow_if_not_a_tenant()
    {
        $this->shouldThrow(ForbiddenException::class)->duringProcess([]);
    }

    public function it_should_process_guid_items()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);
    

        $membershipGuid = Guid::build();
        $items = [
            new SiteMembershipBatchUpdate(
                idType: SiteMembershipBatchIdTypeEnum::GUID,
                id: Guid::build(),
                membershipGuid: $membershipGuid,
                validFrom: new DateTimeImmutable('midnight last month'),
                validTo: new DateTimeImmutable('midnight'),
            )
        ];

        $this->readerServiceMock->getSiteMembership($membershipGuid)
            ->shouldBeCalled()
            ->willReturn(new SiteMembership($membershipGuid, '', 0, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING));

        $this->entitiesBuilderMock->single(Argument::any())
            ->willReturn(new User());

        $this->siteMembershipSubscriptionsServiceMock->addSiteMembershipSubscription(
            Argument::that(
                fn (SiteMembershipSubscriptionDTO $input) =>
                $input->user instanceof User &&
                $input->siteMembership instanceof SiteMembership &&
                $input->isManual === true &&
                $input->validFrom == new DateTimeImmutable('midnight last month') &&
                $input->validTo == new DateTimeImmutable('midnight'),
            ),
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->process($items);
    }

    public function it_should_process_oidc_items()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);
    

        $membershipGuid = Guid::build();
        $items = [
            new SiteMembershipBatchUpdate(
                idType: SiteMembershipBatchIdTypeEnum::OIDC,
                id: '1::subid',
                membershipGuid: $membershipGuid,
                validFrom: new DateTimeImmutable('midnight last month'),
                validTo: new DateTimeImmutable('midnight'),
            )
        ];

        $this->readerServiceMock->getSiteMembership($membershipGuid)
            ->shouldBeCalled()
            ->willReturn(new SiteMembership($membershipGuid, '', 0, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING));

        $this->oidcUserServiceMock->getUserFromSub('subid', 1)
            ->willReturn(new User());

        $this->siteMembershipSubscriptionsServiceMock->addSiteMembershipSubscription(
            Argument::that(
                fn (SiteMembershipSubscriptionDTO $input) =>
                $input->user instanceof User &&
                $input->siteMembership instanceof SiteMembership &&
                $input->isManual === true &&
                $input->validFrom == new DateTimeImmutable('midnight last month') &&
                $input->validTo == new DateTimeImmutable('midnight'),
            ),
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->process($items);
    }

    public function it_should_process_email_items()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);
    

        $membershipGuid = Guid::build();
        $items = [
            new SiteMembershipBatchUpdate(
                idType: SiteMembershipBatchIdTypeEnum::EMAIL,
                id: 'test@minds.com',
                membershipGuid: $membershipGuid,
                validFrom: new DateTimeImmutable('midnight last month'),
                validTo: new DateTimeImmutable('midnight'),
            )
        ];

        $this->readerServiceMock->getSiteMembership($membershipGuid)
            ->shouldBeCalled()
            ->willReturn(new SiteMembership($membershipGuid, '', 0, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING));

        $this->tenantUsersRepositoryMock->getUserGuids(1, null, 'test@minds.com')
            ->willYield([1,2]);

        $this->entitiesBuilderMock->single(Argument::any())
            ->shouldBeCalledTimes(2)
            ->willReturn(new User());

        $this->siteMembershipSubscriptionsServiceMock->addSiteMembershipSubscription(
            Argument::that(
                fn (SiteMembershipSubscriptionDTO $input) =>
                $input->user instanceof User &&
                $input->siteMembership instanceof SiteMembership &&
                $input->isManual === true &&
                $input->validFrom == new DateTimeImmutable('midnight last month') &&
                $input->validTo == new DateTimeImmutable('midnight'),
            ),
        )
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this->process($items);
    }
}
