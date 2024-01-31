<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipGroupsFoundException;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipRolesFoundException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceCurrencyEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;

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
        $productKeys = [];

        foreach ($this->siteMembershipRepository->getSiteMemberships() as $siteMembershipDbInfo) {
            $productKeys["tenant:{$this->config->get('tenant_id')}:{$siteMembershipDbInfo['membership_tier_guid']}"] = $siteMembershipDbInfo['membership_tier_guid'];
        }

        $stripeProducts = $this->stripeProductService->getProductsByKeys(array_keys($productKeys));

        foreach ($stripeProducts as $stripeProduct) {
            $siteMembershipGuid = $productKeys[$stripeProduct->metadata['key']] ?? throw new NotFoundException("Site membership not found for stripe product {$stripeProduct->id}");

            $stripeProductPrice = $this->stripeProductPriceService->getPriceDetailsById($stripeProduct->default_price);

            $billingPeriod = SiteMembershipBillingPeriodEnum::tryFrom($stripeProduct->metadata['billing_period']);
            $pricingModel = $stripeProductPrice->recurring ? SiteMembershipPricingModelEnum::RECURRING : SiteMembershipPricingModelEnum::ONE_TIME;

            $siteMembership = new SiteMembership(
                membershipGuid: $siteMembershipGuid,
                membershipName: $stripeProduct->name,
                membershipPriceInCents: $stripeProductPrice->unit_amount,
                membershipBillingPeriod: $billingPeriod,
                membershipPricingModel: $pricingModel,
                membershipDescription: $stripeProduct->description,
                priceCurrency: ProductPriceCurrencyEnum::tryFrom($stripeProductPrice->currency)->value,
                roles: $this->prepareSiteMembershipRoles($siteMembershipGuid),
                groups: $this->prepareSiteMembershipGroups($siteMembershipGuid)
            );

            $siteMemberships[] = $siteMembership;
        }

        return $siteMemberships;
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
}
