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
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Entities\Group;
use Minds\Exceptions\NotFoundException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;
use ReflectionException;
use Spec\Minds\Common\Traits\CommonMatchers;
use Stripe\Price;
use Stripe\Product;

class SiteMembershipReaderServiceSpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $siteMembershipRepositoryMock;
    private Collaborator $siteMembershipGroupsRepositoryMock;
    private Collaborator $siteMembershipRolesRepositoryMock;
    private Collaborator $stripeProductServiceMock;
    private Collaborator $stripeProductPriceServiceMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $configMock;

    private ReflectionClass $stripeProductMockFactory;
    private ReflectionClass $stripeProductPriceMockFactory;

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
        $this->stripeProductServiceMock = $stripeProductService;
        $this->stripeProductPriceServiceMock = $stripeProductPriceService;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->configMock = $config;

        $this->stripeProductMockFactory = new ReflectionClass(Product::class);
        $this->stripeProductPriceMockFactory = new ReflectionClass(Price::class);

        $this->beConstructedWith(
            $this->siteMembershipRepositoryMock,
            $this->siteMembershipGroupsRepositoryMock,
            $this->siteMembershipRolesRepositoryMock,
            $this->stripeProductServiceMock,
            $this->stripeProductPriceServiceMock,
            $this->entitiesBuilderMock,
            $this->configMock
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
        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(1);

        $availableMemberships = [
            'prod_1' => [
                'membership_tier_guid' => 1,
                'stripe_product_id' => 'prod_1',
                'archived' => false,
            ],
            'prod_2' => [
                'membership_tier_guid' => 2,
                'stripe_product_id' => 'prod_2',
                'archived' => false,
            ],
            'prod_3' => [
                'membership_tier_guid' => 3,
                'stripe_product_id' => 'prod_3',
                'archived' => true,
            ],
        ];

        $this->siteMembershipRepositoryMock->getSiteMemberships()
            ->shouldBeCalledOnce()
            ->willYield($availableMemberships);

        $this->stripeProductServiceMock->getProductsByMetadata(
            [
                'type' => ProductTypeEnum::SITE_MEMBERSHIP->value,
                'tenant_id' => 1,
            ],
            ProductTypeEnum::SITE_MEMBERSHIP,
            $availableMemberships
        )
            ->shouldBeCalledOnce()
            ->willYield([
                $this->generateStripeProductMock(
                    id: 'prod_1',
                    tenantId: 1,
                    priceId: 'price_1',
                    key: 'key_1',
                    type: ProductTypeEnum::SITE_MEMBERSHIP->value,
                    billingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY->value
                ),
                $this->generateStripeProductMock(
                    id: 'prod_2',
                    tenantId: 1,
                    priceId: 'price_2',
                    key: 'key_2',
                    type: ProductTypeEnum::SITE_MEMBERSHIP->value,
                    billingPeriod: SiteMembershipBillingPeriodEnum::YEARLY->value
                ),
            ]);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(1)
            ->shouldBeCalledOnce()
            ->willReturn(['group_1', 'group_2']);

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(2)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->entitiesBuilderMock->single('group_1')
            ->shouldBeCalledOnce()
            ->willReturn($groupMock);

        $this->entitiesBuilderMock->single('group_2')
            ->shouldBeCalledOnce()
            ->willReturn($groupMock);

        $this->stripeProductPriceServiceMock->getPriceDetailsById("price_1")
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeProductPriceMock(
                    id: 'price_1',
                    unitAmount: 1099,
                    type: 'recurring',
                    currency: 'usd',
                    recurring: [
                        'interval' => 'month',
                        'interval_count' => 1,
                    ]
                )
            );

        $this->stripeProductPriceServiceMock->getPriceDetailsById("price_2")
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeProductPriceMock(
                    id: 'price_2',
                    unitAmount: 1599,
                    type: 'one_time',
                    currency: 'usd'
                )
            );

        $this->stripeProductPriceServiceMock->getPriceDetailsById("price_3")
            ->shouldNotBeCalled();

        $this->getSiteMemberships()
            ->shouldContainAnInstanceOf(SiteMembership::class);
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

    /**
     * @param string $id
     * @param int $unitAmount
     * @param string $type
     * @param string $currency
     * @param array|null $recurring
     * @return Price
     * @throws ReflectionException
     */
    private function generateStripeProductPriceMock(
        string $id,
        int    $unitAmount,
        string $type,
        string $currency,
        ?array $recurring = null
    ): Price {
        $mock = $this->stripeProductPriceMockFactory->newInstanceWithoutConstructor();
        $this->stripeProductPriceMockFactory->getProperty('_values')->setValue($mock, [
            'id' => $id,
            'unit_amount' => $unitAmount,
            'type' => $type,
            'recurring' => $recurring ?? null,
            'currency' => $currency
        ]);

        return $mock;
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

    public function it_should_return_empty_array_when_no_stripe_products_found(): void
    {
        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(1);

        $availableMemberships = [
            'prod_1' => [
                'membership_tier_guid' => 1,
                'stripe_product_id' => 'prod_1',
                'archived' => false,
            ],
            'prod_2' => [
                'membership_tier_guid' => 2,
                'stripe_product_id' => 'prod_2',
                'archived' => false,
            ],
            'prod_3' => [
                'membership_tier_guid' => 3,
                'stripe_product_id' => 'prod_3',
                'archived' => true,
            ],
        ];

        $this->siteMembershipRepositoryMock->getSiteMemberships()
            ->shouldBeCalledOnce()
            ->willYield($availableMemberships);

        $this->stripeProductServiceMock->getProductsByMetadata(
            [
                'type' => ProductTypeEnum::SITE_MEMBERSHIP->value,
                'tenant_id' => 1,
            ],
            ProductTypeEnum::SITE_MEMBERSHIP,
            $availableMemberships
        )
            ->shouldBeCalledOnce()
            ->willThrow(NotFoundException::class);

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
                'price_in_cents' => 1099,
            ]);

        $this->stripeProductServiceMock->getProductById("prod_1")
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeProductMock(
                    id: 'prod_1',
                    tenantId: 1,
                    priceId: 'price_1',
                    key: 'key_1',
                    type: ProductTypeEnum::SITE_MEMBERSHIP->value,
                    billingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY->value
                )
            );

        $this->stripeProductPriceServiceMock->getPriceDetailsById("price_1")
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeProductPriceMock(
                    id: 'price_1',
                    unitAmount: 1099,
                    type: 'recurring',
                    currency: 'usd',
                    recurring: [
                        'interval' => 'month',
                        'interval_count' => 1,
                    ]
                )
            );

        $this->siteMembershipGroupsRepositoryMock->getSiteMembershipGroups(1)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->getSiteMembership(1)
            ->shouldBeAnInstanceOf(SiteMembership::class);
    }
}
