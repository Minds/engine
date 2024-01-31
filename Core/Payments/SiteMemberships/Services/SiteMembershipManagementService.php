<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceBillingPeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPricingModelEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;

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
     * @throws ApiErrorException
     */
    public function storeSiteMembership(
        SiteMembership $siteMembership
    ): SiteMembership {
        // TODO: check if network has reached the limit of memberships

        $this->siteMembershipRepository->beginTransaction();
        try {
            $stripeProduct = $this->stripeProductService->createProduct(
                internalProductId: $siteMembership->membershipGuid,
                name: $siteMembership->membershipName,
                priceInCents: $siteMembership->membershipPriceInCents,
                billingPeriod: ProductPriceBillingPeriodEnum::tryFrom($siteMembership->membershipBillingPeriod->value),
                pricingModel: ProductPricingModelEnum::tryFrom($siteMembership->membershipPricingModel->value),
                description: $siteMembership->membershipDescription,
            );

            $this->siteMembershipRepository->storeSiteMembership(
                siteMembership: $siteMembership,
                stripeProductId: $stripeProduct->id
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

            // TODO: delete stripe product

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
     */
    public function updateSiteMembership(
        SiteMembership $siteMembership
    ): SiteMembership {
        $siteMembershipDbInfo = $this->siteMembershipRepository->getSiteMembership($siteMembership->membershipGuid);

        // QUESTION: should we allow the update of roles and groups for an active membership?


        $this->stripeProductService->updateProduct(
            productId: $siteMembershipDbInfo['stripe_product_id'],
            name: $siteMembership->membershipName,
            description: $siteMembership->membershipDescription
        );

        return $siteMembership;
    }

    public function archiveSiteMembership(
        int $siteMembershipGuid
    ): bool {
        $siteMembershipDbInfo = $this->siteMembershipRepository->getSiteMembership($siteMembershipGuid);
        return $this->stripeProductService->archiveProduct(
            productId: $siteMembershipDbInfo['stripe_product_id']
        );

        // QUESTION: should we delete the site membership from the database? Probably not to preserve ongoing subscriptions
    }
}
