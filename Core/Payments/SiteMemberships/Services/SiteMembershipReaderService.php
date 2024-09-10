<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Core\Log\Logger;
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
use Minds\Entities\Group;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;

class SiteMembershipReaderService
{
    public function __construct(
        private readonly SiteMembershipRepository       $siteMembershipRepository,
        private readonly SiteMembershipGroupsRepository $siteMembershipGroupsRepository,
        private readonly SiteMembershipRolesRepository  $siteMembershipRolesRepository,
        private readonly EntitiesBuilder                $entitiesBuilder,
        private readonly Logger                         $logger
    ) {
    }

    /**
     * @param bool $excludeExternal - Whether to exclude external site memberships.
     * @return SiteMembership[]
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function getSiteMemberships(bool $excludeExternal = false): array
    {
        $siteMemberships = [];

        try {
            foreach ($this->siteMembershipRepository->getSiteMemberships($excludeExternal) as $siteMembershipDbInfo) {
                if ($siteMembershipDbInfo['archived']) {
                    continue;
                }

                $siteMemberships[] = $this->prepareSiteMembership($siteMembershipDbInfo, $siteMembershipDbInfo['membership_tier_guid']);
            }
            return $siteMemberships;
        } catch (NoSiteMembershipsFoundException $e) {
            return [];
        }
    }

    /**
     * @param array $siteMembershipDetails
     * @param int $siteMembershipGuid
     * @return SiteMembership
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    private function prepareSiteMembership(array $siteMembershipDetails, int $siteMembershipGuid): SiteMembership
    {
        return new SiteMembership(
            membershipGuid: $siteMembershipGuid,
            membershipName: $siteMembershipDetails['name'],
            membershipPriceInCents: (int)$siteMembershipDetails['price_in_cents'],
            membershipBillingPeriod: SiteMembershipBillingPeriodEnum::from($siteMembershipDetails['billing_period']),
            membershipPricingModel: SiteMembershipPricingModelEnum::from($siteMembershipDetails['pricing_model']),
            stripeProductId: $siteMembershipDetails['stripe_product_id'],
            membershipDescription: $siteMembershipDetails['description'],
            priceCurrency: strtoupper($siteMembershipDetails['currency']),
            roles: $this->prepareSiteMembershipRoles($siteMembershipGuid),
            groups: $this->prepareSiteMembershipGroups($siteMembershipGuid),
            archived: (bool) $siteMembershipDetails['archived'],
            isExternal: (bool) $siteMembershipDetails['is_external'],
            purchaseUrl: $siteMembershipDetails['purchase_url'],
            manageUrl: $siteMembershipDetails['manage_url'],
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

                if (!$group || !($group instanceof Group)) {
                    $this->logger->warning("Group not found with guid: $groupGuid, for site membership $siteMembershipGuid");
                    continue;
                }

                $groups[] = new GroupNode($group);
            }
        } catch (NoSiteMembershipGroupsFoundException $e) {
            $groups = null;
        }

        return $groups;
    }

    /**
     * @param int $siteMembershipGuid
     * @return SiteMembership
     * @throws NoSiteMembershipFoundException
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function getSiteMembership(
        int $siteMembershipGuid
    ): SiteMembership {
        $siteMembershipDbInfo = $this->siteMembershipRepository->getSiteMembership(
            siteMembershipGuid: $siteMembershipGuid
        );

        return $this->prepareSiteMembership($siteMembershipDbInfo, $siteMembershipDbInfo['membership_tier_guid']);
    }
}
