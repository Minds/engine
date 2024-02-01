<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipGroupsFoundException;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipRolesFoundException;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipsFoundException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceCurrencyEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\Product;

class SiteMembershipReaderService
{
    public function __construct(
        private readonly SiteMembershipRepository       $siteMembershipRepository,
        private readonly SiteMembershipGroupsRepository $siteMembershipGroupsRepository,
        private readonly SiteMembershipRolesRepository  $siteMembershipRolesRepository,
        private readonly StripeProductService           $stripeProductService,
        private readonly ProductPriceService            $stripeProductPriceService,
        private readonly EntitiesBuilder                $entitiesBuilder,
        private readonly Config                         $config
    ) {
    }

    /**
     * @return SiteMembership[]
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     */
    public function getSiteMemberships(): array
    {
        $siteMemberships = [];
        $products = [];

        try {
            foreach ($this->siteMembershipRepository->getSiteMemberships() as $siteMembershipDbInfo) {
                $products[$siteMembershipDbInfo['stripe_product_id']] = $siteMembershipDbInfo;
            }
        } catch (NoSiteMembershipsFoundException $e) {
            return [];
        }

        try {
            $stripeProducts = $this->stripeProductService->getProductsByMetadata(
                metadata: [
                    'type' => ProductTypeEnum::SITE_MEMBERSHIP->value,
                    'tenant_id' => $this->config->get('tenant_id') ?? '-1',
                ],
                productType: ProductTypeEnum::SITE_MEMBERSHIP
            );
        } catch (NotFoundException $e) {
            return [];
        }

        foreach ($stripeProducts as $stripeProduct) {
            if (!isset($products[$stripeProduct->id])) {
                continue;
            }

            $siteMembershipDbInfo = $products[$stripeProduct->id];

            if ($siteMembershipDbInfo['archived']) {
                continue;
            }

            $siteMemberships[] = $this->prepareSiteMembership($stripeProduct, $siteMembershipDbInfo['membership_tier_guid']);
        }

        return $siteMemberships;
    }

    /**
     * @param Product $stripeProduct
     * @param int $siteMembershipGuid
     * @return SiteMembership
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    private function prepareSiteMembership(Product $stripeProduct, int $siteMembershipGuid): SiteMembership
    {
        $stripeProductPrice = $this->stripeProductPriceService->getPriceDetailsById($stripeProduct->default_price);

        $billingPeriod = SiteMembershipBillingPeriodEnum::tryFrom($stripeProduct->metadata['billing_period']);
        $pricingModel = $stripeProductPrice->recurring ? SiteMembershipPricingModelEnum::RECURRING : SiteMembershipPricingModelEnum::ONE_TIME;

        return new SiteMembership(
            membershipGuid: $siteMembershipGuid,
            membershipName: $stripeProduct->name,
            membershipPriceInCents: $stripeProductPrice->unit_amount,
            membershipBillingPeriod: $billingPeriod,
            membershipPricingModel: $pricingModel,
            membershipDescription: $stripeProduct->description,
            priceCurrency: strtoupper(ProductPriceCurrencyEnum::tryFrom($stripeProductPrice->currency)->value),
            roles: $this->prepareSiteMembershipRoles($siteMembershipGuid),
            groups: $this->prepareSiteMembershipGroups($siteMembershipGuid)
        );
    }

    /**
     * @param int $siteMembershipGuid
     * @return array|null
     * @throws ServerErrorException
     */
    private function prepareSiteMembershipRoles(int $siteMembershipGuid): ?array
    {
        try {
            return $this->siteMembershipRolesRepository->getSiteMembershipRoles(
                siteMembershipGuid: $siteMembershipGuid
            );
        } catch (NoSiteMembershipRolesFoundException $e) {
            return null;
        }
    }

    /**
     * @param int $siteMembershipGuid
     * @return array|null
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    private function prepareSiteMembershipGroups(int $siteMembershipGuid): ?array
    {
        try {
            $groupGuids = $this->siteMembershipGroupsRepository->getSiteMembershipGroups(
                siteMembershipGuid: $siteMembershipGuid
            );

            $groups = [];
            foreach ($groupGuids as $groupGuid) {
                $group = $this->entitiesBuilder->single($groupGuid);
                if (!$group) {
                    throw new NotFoundException("Group $groupGuid not found.");
                }
                $groups[] = new GroupNode($group);
            }
        } catch (NoSiteMembershipGroupsFoundException $e) {
            $groups = null;
        }

        return $groups;
    }

    /**
     * @throws NotFoundException
     * @throws ApiErrorException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     * @throws NoSiteMembershipFoundException
     */
    public function getSiteMembership(
        int $siteMembershipGuid
    ): SiteMembership {
        $siteMembershipDbInfo = $this->siteMembershipRepository->getSiteMembership(
            siteMembershipGuid: $siteMembershipGuid
        );

        $stripeProduct = $this->stripeProductService->getProductById($siteMembershipDbInfo['stripe_product_id']);

        return $this->prepareSiteMembership($stripeProduct, $siteMembershipGuid);
    }
}
