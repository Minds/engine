<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipsFoundException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\Group;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionException;
use Spec\Minds\Common\Traits\CommonMatchers;

class SiteMembershipReaderServiceSpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $siteMembershipRepositoryMock;
    private Collaborator $siteMembershipGroupsRepositoryMock;
    private Collaborator $siteMembershipRolesRepositoryMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $loggerMock;

    public function let(
        SiteMembershipRepository       $siteMembershipRepository,
        SiteMembershipGroupsRepository $siteMembershipGroupsRepository,
        SiteMembershipRolesRepository  $siteMembershipRolesRepository,
        EntitiesBuilder                $entitiesBuilder,
        Logger                         $logger,
    ): void {
        $this->siteMembershipRepositoryMock = $siteMembershipRepository;
        $this->siteMembershipGroupsRepositoryMock = $siteMembershipGroupsRepository;
        $this->siteMembershipRolesRepositoryMock = $siteMembershipRolesRepository;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->loggerMock = $logger;

        $this->beConstructedWith(
            $this->siteMembershipRepositoryMock,
            $this->siteMembershipGroupsRepositoryMock,
            $this->siteMembershipRolesRepositoryMock,
            $this->entitiesBuilderMock,
            $this->loggerMock,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(SiteMembershipReaderService::class);
    }

    /**
     * @param Group $groupMock
     * @return void
     * @throws ReflectionException
     */
    public function it_should_get_memberships(
        Group $groupMock
    ): void {
        $availableMemberships = [
            [
                'membership_tier_guid' => 1,
                'stripe_product_id' => 'prod_1',
                'name' => 'Membership 1',
                'description' => 'Membership 1 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'recurring',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => false,
                'purchase_url' => null,
                'manage_url' => null,
            ],
            [
                'membership_tier_guid' => 2,
                'stripe_product_id' => 'prod_2',
                'name' => 'Membership 2',
                'description' => 'Membership 2 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'recurring',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => false,
                'purchase_url' => null,
                'manage_url' => null,
            ],
            [
                'membership_tier_guid' => 3,
                'stripe_product_id' => 'prod_3',
                'name' => 'Membership 3',
                'description' => 'Membership 3 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'one_time',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => true,
                'purchase_url' => 'http://foo/bar',
                'manage_url' => 'http://bar/foo'
            ],
        ];

        $this->siteMembershipRepositoryMock->getSiteMemberships(excludeExternal: false)
            ->shouldBeCalledOnce()
            ->willYield($availableMemberships);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(1)
            ->shouldBeCalledOnce()
            ->willReturn(['group_1', 'group_2']);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(2)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(3)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->entitiesBuilderMock->single('group_1')
            ->shouldBeCalledOnce()
            ->willReturn($groupMock);

        $this->entitiesBuilderMock->single('group_2')
            ->shouldBeCalledOnce()
            ->willReturn($groupMock);

        $memberships = $this->getSiteMemberships();
        $memberships->shouldContainAnInstanceOf(SiteMembership::class);

        $memberships[2]->isExternal->shouldBe(true);
        $memberships[2]->purchaseUrl->shouldBe('http://foo/bar');
        $memberships[2]->manageUrl->shouldBe('http://bar/foo');
    }

    /**
     * @param Group $groupMock
     * @return void
     * @throws ReflectionException
     */
    public function it_should_get_memberships_and_exclude_external_memberships(
        Group $groupMock
    ): void {
        $availableMemberships = [
            [
                'membership_tier_guid' => 1,
                'stripe_product_id' => 'prod_1',
                'name' => 'Membership 1',
                'description' => 'Membership 1 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'recurring',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => false,
                'purchase_url' => null,
                'manage_url' => null,
            ],
            [
                'membership_tier_guid' => 2,
                'stripe_product_id' => 'prod_2',
                'name' => 'Membership 2',
                'description' => 'Membership 2 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'recurring',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => false,
                'purchase_url' => null,
                'manage_url' => null,
            ],
            [
                'membership_tier_guid' => 3,
                'stripe_product_id' => 'prod_3',
                'name' => 'Membership 3',
                'description' => 'Membership 3 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'one_time',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => false,
                'purchase_url' => 'http://foo/bar',
                'manage_url' => 'http://bar/foo'
            ],
        ];

        $this->siteMembershipRepositoryMock->getSiteMemberships(excludeExternal: true)
            ->shouldBeCalledOnce()
            ->willYield($availableMemberships);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(1)
            ->shouldBeCalledOnce()
            ->willReturn(['group_1', 'group_2']);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(2)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(3)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->entitiesBuilderMock->single('group_1')
            ->shouldBeCalledOnce()
            ->willReturn($groupMock);

        $this->entitiesBuilderMock->single('group_2')
            ->shouldBeCalledOnce()
            ->willReturn($groupMock);

        $memberships = $this->getSiteMemberships(excludeExternal: true);
        $memberships->shouldContainAnInstanceOf(SiteMembership::class);

        $memberships[2]->isExternal->shouldBe(false);
        $memberships[2]->purchaseUrl->shouldBe('http://foo/bar');
        $memberships[2]->manageUrl->shouldBe('http://bar/foo');
    }

    /**
     * @param Group $groupMock
     * @return void
     * @throws ReflectionException
     */
    public function it_should_get_memberships_and_omit_and_not_found_groups(
        Group $groupMock
    ): void {
        $availableMemberships = [
            [
                'membership_tier_guid' => 1,
                'stripe_product_id' => 'prod_1',
                'name' => 'Membership 1',
                'description' => 'Membership 1 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'recurring',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => false,
                'purchase_url' => null,
                'manage_url' => null,
            ],
            [
                'membership_tier_guid' => 2,
                'stripe_product_id' => 'prod_2',
                'name' => 'Membership 2',
                'description' => 'Membership 2 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'recurring',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => false,
                'purchase_url' => null,
                'manage_url' => null,
            ],
            [
                'membership_tier_guid' => 3,
                'stripe_product_id' => 'prod_3',
                'name' => 'Membership 3',
                'description' => 'Membership 3 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'one_time',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => true,
                'purchase_url' => 'http://foo/bar',
                'manage_url' => 'http://bar/foo'
            ],
        ];

        $this->siteMembershipRepositoryMock->getSiteMemberships(excludeExternal: false)
            ->shouldBeCalledOnce()
            ->willYield($availableMemberships);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(1)
            ->shouldBeCalledOnce()
            ->willReturn(['group_1', 'group_2']);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(2)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(3)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->entitiesBuilderMock->single('group_1')
            ->shouldBeCalledOnce()
            ->willReturn($groupMock);

        $this->entitiesBuilderMock->single('group_2')
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->loggerMock->warning("Group not found with guid: group_2, for site membership 1")
            ->shouldBeCalledOnce();

        $memberships = $this->getSiteMemberships();
        $memberships->shouldContainAnInstanceOf(SiteMembership::class);
    }

    /**
     * @return void
     */
    public function it_should_return_empty_array_when_no_memberships(): void
    {
        $this->siteMembershipRepositoryMock->getSiteMemberships(excludeExternal: false)
            ->shouldBeCalledOnce()
            ->willThrow(NoSiteMembershipsFoundException::class);

        $this->getSiteMemberships()->shouldHaveALengthOf(0);
    }

    public function it_should_get_membership(): void
    {
        $this->siteMembershipRepositoryMock->getSiteMembership(
            1
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'membership_tier_guid' => 1,
                'stripe_product_id' => 'prod_1',
                'name' => 'Membership 1',
                'description' => 'Membership 1 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'recurring',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => false,
                'purchase_url' => null,
                'manage_url' => null,
            ]);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(1)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->getSiteMembership(1)
            ->shouldBeAnInstanceOf(SiteMembership::class);
    }

    
    public function it_should_get_an_external_membership(): void
    {
        $this->siteMembershipRepositoryMock->getSiteMembership(
            1
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'membership_tier_guid' => 1,
                'stripe_product_id' => 'prod_1',
                'name' => 'Membership 1',
                'description' => 'Membership 1 description',
                'billing_period' => SiteMembershipBillingPeriodEnum::MONTHLY->value,
                'pricing_model' => 'recurring',
                'currency' => 'usd',
                'price_in_cents' => 1099,
                'archived' => false,
                'is_external' => true,
                'purchase_url' => 'http://foo/bar',
                'manage_url' => 'http://bar/foo'
            ]);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(1)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $membership = $this->getSiteMembership(1);
        $membership->shouldBeAnInstanceOf(SiteMembership::class);
        $membership->isExternal->shouldBe(true);
        $membership->purchaseUrl->shouldBe('http://foo/bar');
        $membership->manageUrl->shouldBe('http://bar/foo');
    }
}
