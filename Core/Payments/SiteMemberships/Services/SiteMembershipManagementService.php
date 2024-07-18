<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
use Minds\Core\Payments\SiteMemberships\Exceptions\TooManySiteMembershipsException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceBillingPeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceCurrencyEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPricingModelEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Exceptions\StripeNotConfiguredException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Psr\SimpleCache\InvalidArgumentException;

class SiteMembershipManagementService
{
    public function __construct(
        private readonly SiteMembershipRepository       $siteMembershipRepository,
        private readonly SiteMembershipGroupsRepository $siteMembershipGroupsRepository,
        private readonly SiteMembershipRolesRepository  $siteMembershipRolesRepository,
        private readonly StripeProductService           $stripeProductService,
        private readonly Config                         $config
    ) {
    }

    /**
     * @param SiteMembership $siteMembership
     * @return SiteMembership
     * @throws InvalidArgumentException
     * @throws ServerErrorException
     * @throws TooManySiteMembershipsException
     */
    public function storeSiteMembership(
        SiteMembership $siteMembership
    ): SiteMembership {
        if ($this->config->get('tenant_id')) {
            $totalSiteMemberships = $this->siteMembershipRepository->getTotalSiteMemberships();
            $tenant = $this->config->get('tenant');
            $maxAllowedActiveMemberships = $this->config->get('multi_tenant')['plan_memberships'][$tenant->plan->name];
            if ($totalSiteMemberships >= $maxAllowedActiveMemberships) {
                throw new TooManySiteMembershipsException("Your network plan only allows $maxAllowedActiveMemberships active membership(s). Archive a membership before creating a new one.");
            }
        }

        $this->siteMembershipRepository->beginTransaction();

        if (!$siteMembership->isExternal) {
            try {
                $stripeProduct = $this->stripeProductService->createProduct(
                    internalProductId: $siteMembership->membershipGuid,
                    name: $siteMembership->membershipName,
                    priceInCents: $siteMembership->membershipPriceInCents,
                    billingPeriod: ProductPriceBillingPeriodEnum::tryFrom($siteMembership->membershipBillingPeriod->value),
                    pricingModel: ProductPricingModelEnum::tryFrom($siteMembership->membershipPricingModel->value),
                    productType: ProductTypeEnum::SITE_MEMBERSHIP,
                    currency: ProductPriceCurrencyEnum::USD,
                    description: $siteMembership->membershipDescription
                );
            } catch (StripeNotConfiguredException $e) {
                // Not having stripe setup is ok... so long as the payment link is set
                throw new UserErrorException("You have not configured stripe. Please use an external membership instead.");
            }
        } else {
            $stripeProduct = null;
        }

        try {
            $this->siteMembershipRepository->storeSiteMembership(
                siteMembership: $siteMembership,
                stripeProductId: $stripeProduct?->id
            );

            if ($roles = $siteMembership->getRoles()) {
                $this->siteMembershipRolesRepository->storeSiteMembershipRoles(
                    siteMembershipGuid: $siteMembership->membershipGuid,
                    siteMembershipRoles: $roles
                );
            }

            if ($siteMembership->getGroups()) {
                $this->siteMembershipGroupsRepository->storeSiteMembershipGroups(
                    siteMembershipGuid: $siteMembership->membershipGuid,
                    siteMembershipGroups: $siteMembership->getGroups()
                );
            }
        } catch (ServerErrorException $e) {
            $this->siteMembershipRepository->rollbackTransaction();

            $this->stripeProductService->deleteProduct($stripeProduct->id);

            throw new ServerErrorException(
                message: "Failed to store site membership.",
                previous: $e
            );
        }

        $this->siteMembershipRepository->commitTransaction();
        return $siteMembership;
    }

    /**
     * @param SiteMembership $siteMembership
     * @return SiteMembership
     * @throws InvalidArgumentException
     * @throws ServerErrorException
     * @throws NoSiteMembershipFoundException
     */
    public function updateSiteMembership(
        SiteMembership $siteMembership
    ): SiteMembership {
        $siteMembershipDbInfo = $this->siteMembershipRepository->getSiteMembership($siteMembership->membershipGuid);

        $this->siteMembershipRepository->beginTransaction();

        try {
            $this->siteMembershipRolesRepository->deleteSiteMembershipRoles($siteMembership->membershipGuid);
            if ($roles = $siteMembership->getRoles()) {
                $this->siteMembershipRolesRepository->storeSiteMembershipRoles(
                    siteMembershipGuid: $siteMembership->membershipGuid,
                    siteMembershipRoles: $roles
                );
            }

            $this->siteMembershipGroupsRepository->deleteSiteMembershipGroups($siteMembership->membershipGuid);
            if ($groups = $siteMembership->getGroups()) {
                $this->siteMembershipGroupsRepository->storeSiteMembershipGroups(
                    siteMembershipGuid: $siteMembership->membershipGuid,
                    siteMembershipGroups: $groups
                );
            }

            $this->siteMembershipRepository->updateSiteMembership($siteMembership);
        } catch (ServerErrorException $e) {
            $this->siteMembershipRepository->rollbackTransaction();

            throw new ServerErrorException(
                message: "Failed to update site membership.",
                previous: $e
            );
        }


        if (!$siteMembership->isExternal) {
            try {
                $this->stripeProductService->updateProduct(
                    productId: $siteMembershipDbInfo['stripe_product_id'],
                    name: $siteMembership->membershipName,
                    description: $siteMembership->membershipDescription
                );
            } catch (Exception $e) {
                $this->siteMembershipRepository->rollbackTransaction();

                throw new ServerErrorException(
                    message: "Failed to update site membership.",
                    previous: $e
                );
            }
        }

        $this->siteMembershipRepository->commitTransaction();

        return $siteMembership;
    }

    /**
     * @param int $siteMembershipGuid
     * @return bool
     * @throws NoSiteMembershipFoundException
     * @throws ServerErrorException
     */
    public function archiveSiteMembership(
        int $siteMembershipGuid
    ): bool {
        $siteMembershipDbInfo = $this->siteMembershipRepository->getSiteMembership($siteMembershipGuid);
        
        if ($siteMembershipDbInfo['stripe_product_id'] ?? null) {
            $this->stripeProductService->archiveProduct(
                productId: $siteMembershipDbInfo['stripe_product_id']
            );
        }

        $this->siteMembershipRepository->archiveSiteMembership($siteMembershipGuid);
        return true;
    }
}
