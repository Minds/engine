<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
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
        $stripeProduct = $this->stripeProductService->createProduct(
            internalProductId: $siteMembership->membershipGuid,
            name: $siteMembership->membershipName,
            priceInCents: $siteMembership->membershipPriceInCents,
            billingPeriod: ProductPriceBillingPeriodEnum::tryFrom($siteMembership->membershipBillingPeriod->value),
            pricingModel: ProductPricingModelEnum::tryFrom($siteMembership->membershipPricingModel->value),
            description: $siteMembership->membershipDescription,
        );
        try {
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

        // QUESTION: should we allow the update of roles and groups for an active membership?
        // ANSWER: Yes

        // TODO: Delete existing roles and re-insert new ones
        // TODO: Delete existing groups and re-insert new ones

        $this->stripeProductService->updateProduct(
            productId: $siteMembershipDbInfo['stripe_product_id'],
            name: $siteMembership->membershipName,
            description: $siteMembership->membershipDescription
        );

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
        return $this->stripeProductService->archiveProduct(
            productId: $siteMembershipDbInfo['stripe_product_id']
        );

        // QUESTION: should we delete the site membership from the database? Probably not to preserve ongoing subscriptions
        // ANSWER: we should flag the site membership as archived in our database
    }
}
