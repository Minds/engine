<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipsFoundException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
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

    public function let(
        SiteMembershipRepository       $siteMembershipRepository,
        SiteMembershipGroupsRepository $siteMembershipGroupsRepository,
        SiteMembershipRolesRepository  $siteMembershipRolesRepository,
        StripeProductService           $stripeProductService,
        StripeProductPriceService      $stripeProductPriceService,
        EntitiesBuilder                $entitiesBuilder,
        Config                         $config
    ): void {
        $this->siteMembershipRepositoryMock = $siteMembershipRepository;
        $this->siteMembershipGroupsRepositoryMock = $siteMembershipGroupsRepository;
        $this->siteMembershipRolesRepositoryMock = $siteMembershipRolesRepository;
        $this->entitiesBuilderMock = $entitiesBuilder;

        $this->beConstructedWith(
            $this->siteMembershipRepositoryMock,
            $this->siteMembershipGroupsRepositoryMock,
            $this->siteMembershipRolesRepositoryMock,
            $this->entitiesBuilderMock,
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
            ],
        ];

        $this->siteMembershipRepositoryMock->getSiteMemberships()
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

        $this->getSiteMemberships()
            ->shouldContainAnInstanceOf(SiteMembership::class);
    }

    /**
     * @return void
     */
    public function it_should_return_empty_array_when_no_memberships(): void
    {
        $this->siteMembershipRepositoryMock->getSiteMemberships()
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
            ]);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(1)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->getSiteMembership(1)
            ->shouldBeAnInstanceOf(SiteMembership::class);
    }
}
