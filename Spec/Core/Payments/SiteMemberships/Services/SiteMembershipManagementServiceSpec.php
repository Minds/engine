<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\TooManySiteMembershipsException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipManagementService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceBillingPeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceCurrencyEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPricingModelEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;
use ReflectionException;
use Stripe\Product;

class SiteMembershipManagementServiceSpec extends ObjectBehavior
{
    private readonly Collaborator $siteMembershipRepositoryMock;
    private readonly Collaborator $siteMembershipGroupsRepositoryMock;
    private readonly Collaborator $siteMembershipRolesRepositoryMock;
    private readonly Collaborator $stripeProductServiceMock;
    private readonly Collaborator $configMock;

    private ReflectionClass $stripeProductMockFactory;

    public function let(
        SiteMembershipRepository       $siteMembershipRepository,
        SiteMembershipGroupsRepository $siteMembershipGroupsRepository,
        SiteMembershipRolesRepository  $siteMembershipRolesRepository,
        StripeProductService           $stripeProductService,
        Config                         $config
    ): void {
        $this->siteMembershipRepositoryMock = $siteMembershipRepository;
        $this->siteMembershipGroupsRepositoryMock = $siteMembershipGroupsRepository;
        $this->siteMembershipRolesRepositoryMock = $siteMembershipRolesRepository;
        $this->stripeProductServiceMock = $stripeProductService;
        $this->configMock = $config;

        $this->stripeProductMockFactory = new ReflectionClass(Product::class);

        $this->beConstructedWith(
            $this->siteMembershipRepositoryMock,
            $this->siteMembershipGroupsRepositoryMock,
            $this->siteMembershipRolesRepositoryMock,
            $this->stripeProductServiceMock,
            $this->configMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(SiteMembershipManagementService::class);
    }

    public function it_should_throw_too_many_memberships_exception(
        SiteMembership $siteMembershipMock
    ): void {
        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->configMock->get('tenant')
            ->shouldBeCalledOnce()
            ->willReturn((object)[
                'plan' => (object)[
                    'name' => 'plan_name'
                ]
            ]);

        $this->configMock->get('multi_tenant')
            ->shouldBeCalledOnce()
            ->willReturn([
                'plan_memberships' => [
                    'plan_name' => 1
                ]
            ]);


        $this->shouldThrow(TooManySiteMembershipsException::class)->during('storeSiteMembership', [$siteMembershipMock]);
    }

    public function it_should_store_membership(): void
    {
        $siteMembershipMock = $this->generateSiteMembership(
            1,
            'Membership 1',
            1599,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING
        );
        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->configMock->get('tenant')
            ->shouldBeCalledOnce()
            ->willReturn((object)[
                'plan' => (object)[
                    'name' => 'plan_name'
                ]
            ]);

        $this->configMock->get('multi_tenant')
            ->shouldBeCalledOnce()
            ->willReturn([
                'plan_memberships' => [
                    'plan_name' => 10
                ]
            ]);

        $this->siteMembershipRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->stripeProductServiceMock->createProduct(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->membershipName,
            $siteMembershipMock->membershipPriceInCents,
            ProductPriceBillingPeriodEnum::tryFrom($siteMembershipMock->membershipBillingPeriod->value),
            ProductPricingModelEnum::tryFrom($siteMembershipMock->membershipPricingModel->value),
            ProductTypeEnum::SITE_MEMBERSHIP,
            ProductPriceCurrencyEnum::USD,
            $siteMembershipMock->membershipDescription
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock(
                'prod_1',
                1,
                'price_1',
                'key',
                'type',
                'billing_period'
            ));

        $this->siteMembershipRepositoryMock->storeSiteMembership(
            $siteMembershipMock,
            'prod_1'
        )
            ->shouldBeCalledOnce();

        $this->siteMembershipRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->storeSiteMembership($siteMembershipMock)->shouldBeLike($siteMembershipMock);
    }

    public function it_should_store_external_membership(): void
    {
        $siteMembershipMock = $this->generateSiteMembership(
            1,
            'Membership 1',
            1599,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING,
            '',
            [],
            [],
            true,
        );
        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->configMock->get('tenant')
            ->shouldBeCalledOnce()
            ->willReturn((object)[
                'plan' => (object)[
                    'name' => 'plan_name'
                ]
            ]);

        $this->configMock->get('multi_tenant')
            ->shouldBeCalledOnce()
            ->willReturn([
                'plan_memberships' => [
                    'plan_name' => 10
                ]
            ]);

        $this->siteMembershipRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->stripeProductServiceMock->createProduct(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->membershipName,
            $siteMembershipMock->membershipPriceInCents,
            ProductPriceBillingPeriodEnum::tryFrom($siteMembershipMock->membershipBillingPeriod->value),
            ProductPricingModelEnum::tryFrom($siteMembershipMock->membershipPricingModel->value),
            ProductTypeEnum::SITE_MEMBERSHIP,
            ProductPriceCurrencyEnum::USD,
            $siteMembershipMock->membershipDescription
        )
            ->shouldNotBeCalled();

        $this->siteMembershipRepositoryMock->storeSiteMembership(
            $siteMembershipMock,
            null,
        )
            ->shouldBeCalledOnce();

        $this->siteMembershipRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->storeSiteMembership($siteMembershipMock)->shouldBeLike($siteMembershipMock);
    }


    private function generateSiteMembership(
        int                             $membershipGuid,
        string                          $membershipName,
        int                             $membershipPriceInCents,
        SiteMembershipBillingPeriodEnum $membershipBillingPeriod,
        SiteMembershipPricingModelEnum  $membershipPricingModel,
        string|null                     $membershipDescription = null,
        ?array                          $roles = null,
        ?array                          $groups = null,
        bool                            $isExternal = false,
    ): SiteMembership {
        return new SiteMembership(
            membershipGuid: $membershipGuid,
            membershipName: $membershipName,
            membershipPriceInCents: $membershipPriceInCents,
            membershipBillingPeriod: $membershipBillingPeriod,
            membershipPricingModel: $membershipPricingModel,
            membershipDescription: $membershipDescription,
            roles: $roles,
            groups: $groups,
            isExternal: $isExternal,
        );
    }

    /**
     * @param string $id
     * @param int $tenantId
     * @param string $priceId
     * @param string $key
     * @param string $type
     * @param string $billingPeriod
     * @return Product
     * @throws ReflectionException
     */
    private function generateStripeProductMock(
        string $id,
        int    $tenantId,
        string $priceId,
        string $key,
        string $type,
        string $billingPeriod
    ): Product {
        $mock = $this->stripeProductMockFactory->newInstanceWithoutConstructor();
        $this->stripeProductMockFactory->getProperty('_values')->setValue($mock, [
            'id' => $id,
            'name' => "Product $id",
            'metadata' => [
                'key' => $key,
                'tenant_id' => $tenantId,
                'type' => $type,
                'billing_period' => $billingPeriod
            ],
            'default_price' => $priceId
        ]);

        return $mock;
    }

    public function it_should_store_membership_with_roles(): void
    {
        $siteMembershipMock = $this->generateSiteMembership(
            1,
            'Membership 1',
            1599,
            SiteMembershipBillingPeriodEnum::MONTHLY,
            SiteMembershipPricingModelEnum::RECURRING,
            'Membership description',
            [1, 2]
        );
        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->configMock->get('tenant')
            ->shouldBeCalledOnce()
            ->willReturn((object)[
                'plan' => (object)[
                    'name' => 'plan_name'
                ]
            ]);

        $this->configMock->get('multi_tenant')
            ->shouldBeCalledOnce()
            ->willReturn([
                'plan_memberships' => [
                    'plan_name' => 10
                ]
            ]);

        $this->siteMembershipRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->stripeProductServiceMock->createProduct(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->membershipName,
            $siteMembershipMock->membershipPriceInCents,
            ProductPriceBillingPeriodEnum::tryFrom($siteMembershipMock->membershipBillingPeriod->value),
            ProductPricingModelEnum::tryFrom($siteMembershipMock->membershipPricingModel->value),
            ProductTypeEnum::SITE_MEMBERSHIP,
            ProductPriceCurrencyEnum::USD,
            $siteMembershipMock->membershipDescription
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock(
                'prod_1',
                1,
                'price_1',
                'key',
                'type',
                'billing_period'
            ));

        $this->siteMembershipRepositoryMock->storeSiteMembership(
            $siteMembershipMock,
            'prod_1'
        )
            ->shouldBeCalledOnce();

        $this->siteMembershipRolesRepositoryMock->storeSiteMembershipRoles(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->getRoles()
        )
            ->shouldBeCalledOnce();

        $this->siteMembershipRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->storeSiteMembership($siteMembershipMock)->shouldBeLike($siteMembershipMock);
    }

    public function it_should_store_membership_with_groups(): void
    {
        $siteMembershipMock = $this->generateSiteMembership(
            membershipGuid: 1,
            membershipName: 'Membership 1',
            membershipPriceInCents: 1599,
            membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY,
            membershipPricingModel: SiteMembershipPricingModelEnum::RECURRING,
            membershipDescription: 'Membership description',
            groups: [
                'group_1',
                'group_2'
            ]
        );
        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->configMock->get('tenant')
            ->shouldBeCalledOnce()
            ->willReturn((object)[
                'plan' => (object)[
                    'name' => 'plan_name'
                ]
            ]);

        $this->configMock->get('multi_tenant')
            ->shouldBeCalledOnce()
            ->willReturn([
                'plan_memberships' => [
                    'plan_name' => 10
                ]
            ]);

        $this->siteMembershipRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->stripeProductServiceMock->createProduct(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->membershipName,
            $siteMembershipMock->membershipPriceInCents,
            ProductPriceBillingPeriodEnum::tryFrom($siteMembershipMock->membershipBillingPeriod->value),
            ProductPricingModelEnum::tryFrom($siteMembershipMock->membershipPricingModel->value),
            ProductTypeEnum::SITE_MEMBERSHIP,
            ProductPriceCurrencyEnum::USD,
            $siteMembershipMock->membershipDescription
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock(
                'prod_1',
                1,
                'price_1',
                'key',
                'type',
                'billing_period'
            ));

        $this->siteMembershipRepositoryMock->storeSiteMembership(
            $siteMembershipMock,
            'prod_1'
        )
            ->shouldBeCalledOnce();

        $this->siteMembershipRolesRepositoryMock->storeSiteMembershipRoles(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->getRoles()
        )
            ->shouldNotBeCalled();

        $this->siteMembershipGroupsRepositoryMock->storeSiteMembershipGroups(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->getGroups()
        )
            ->shouldBeCalledOnce();


        $this->siteMembershipRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->storeSiteMembership($siteMembershipMock)->shouldBeLike($siteMembershipMock);
    }

    public function it_should_store_membership_with_roles_and_groups(): void
    {
        $siteMembershipMock = $this->generateSiteMembership(
            membershipGuid: 1,
            membershipName: 'Membership 1',
            membershipPriceInCents: 1599,
            membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY,
            membershipPricingModel: SiteMembershipPricingModelEnum::RECURRING,
            membershipDescription: 'Membership description',
            roles: [1, 2],
            groups: [
                'group_1',
                'group_2'
            ]
        );
        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->configMock->get('tenant')
            ->shouldBeCalledOnce()
            ->willReturn((object)[
                'plan' => (object)[
                    'name' => 'plan_name'
                ]
            ]);

        $this->configMock->get('multi_tenant')
            ->shouldBeCalledOnce()
            ->willReturn([
                'plan_memberships' => [
                    'plan_name' => 10
                ]
            ]);

        $this->siteMembershipRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->stripeProductServiceMock->createProduct(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->membershipName,
            $siteMembershipMock->membershipPriceInCents,
            ProductPriceBillingPeriodEnum::tryFrom($siteMembershipMock->membershipBillingPeriod->value),
            ProductPricingModelEnum::tryFrom($siteMembershipMock->membershipPricingModel->value),
            ProductTypeEnum::SITE_MEMBERSHIP,
            ProductPriceCurrencyEnum::USD,
            $siteMembershipMock->membershipDescription
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock(
                'prod_1',
                1,
                'price_1',
                'key',
                'type',
                'billing_period'
            ));

        $this->siteMembershipRepositoryMock->storeSiteMembership(
            $siteMembershipMock,
            'prod_1'
        )
            ->shouldBeCalledOnce();

        $this->siteMembershipRolesRepositoryMock->storeSiteMembershipRoles(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->getRoles()
        )
            ->shouldBeCalledOnce();

        $this->siteMembershipGroupsRepositoryMock->storeSiteMembershipGroups(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->getGroups()
        )
            ->shouldBeCalledOnce();


        $this->siteMembershipRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->storeSiteMembership($siteMembershipMock)->shouldBeLike($siteMembershipMock);
    }

    public function it_should_update_membership(): void
    {
        $siteMembershipMock = $this->generateSiteMembership(
            membershipGuid: 1,
            membershipName: 'Membership 1',
            membershipPriceInCents: 1599,
            membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY,
            membershipPricingModel: SiteMembershipPricingModelEnum::RECURRING,
            membershipDescription: 'Membership description',
        );

        $this->siteMembershipRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->siteMembershipRepositoryMock->getSiteMembership($siteMembershipMock->membershipGuid)
            ->shouldBeCalledOnce()
            ->willReturn([
                'membership_tier_guid' => 1,
                'stripe_product_id' => 'prod_1',
                'archived' => false
            ]);
        $this->siteMembershipRolesRepositoryMock->deleteSiteMembershipRoles($siteMembershipMock->membershipGuid)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->siteMembershipRolesRepositoryMock->storeSiteMembershipRoles(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->getRoles()
        )
            ->shouldNotBeCalled();


        $this->siteMembershipGroupsRepositoryMock->deleteSiteMembershipGroups($siteMembershipMock->membershipGuid)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->siteMembershipGroupsRepositoryMock->storeSiteMembershipGroups(
            $siteMembershipMock->membershipGuid,
            $siteMembershipMock->getGroups()
        )
            ->shouldNotBeCalled();

        $this->siteMembershipRepositoryMock->updateSiteMembership(
            $siteMembershipMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->stripeProductServiceMock->updateProduct(
            'prod_1',
            $siteMembershipMock->membershipName,
            $siteMembershipMock->membershipDescription
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock(
                'prod_1',
                1,
                'price_1',
                'key',
                'type',
                'billing_period'
            ));

        $this->siteMembershipRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->updateSiteMembership($siteMembershipMock)->shouldBeLike($siteMembershipMock);
    }

    public function it_should_archive_membership(): void
    {
        $this->siteMembershipRepositoryMock->getSiteMembership(1)
            ->shouldBeCalledOnce()
            ->willReturn([
                'membership_tier_guid' => 1,
                'stripe_product_id' => 'prod_1',
                'archived' => false
            ]);
        $this->stripeProductServiceMock->archiveProduct('prod_1')
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->siteMembershipRepositoryMock->archiveSiteMembership(1)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->archiveSiteMembership(1)->shouldBe(true);
    }
}
