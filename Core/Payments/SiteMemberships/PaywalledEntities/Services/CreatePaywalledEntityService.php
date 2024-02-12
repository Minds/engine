<?php
namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services;

use Minds\Core\Guid;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\PaywalledEntitiesRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\Activity;
use Minds\Exceptions\UserErrorException;

class CreatePaywalledEntityService
{
    public function __construct(
        private PaywalledEntitiesRepository $paywalledEntitiesRepository,
        private SiteMembershipReaderService $siteMembershipReaderService,
    ) {
        
    }

    /**
     * Pairs each membership to the entity
     * @param int[] $membershipGuids
     */
    public function setupMemberships(Activity $entity, array $membershipGuids): bool
    {
        // We need a guid, if there isn't one, we will create one in advanced
        if (!$entity->getGuid()) {
            $entity->guid = Guid::build();
        }

        $entity->setSiteMembership(true);
            
        // Validate the guids
        if ($membershipGuids === [ -1 ]) {
            // apply to all
        } else {
            $availableMembershipGuids = array_map(function (SiteMembership $siteMembership) {
                return $siteMembership->membershipGuid;
            }, $this->siteMembershipReaderService->getSiteMemberships());

            foreach ($membershipGuids as $membershipGuid) {
                if (!in_array($membershipGuid, $availableMembershipGuids, true)) {
                    throw new UserErrorException("Could not find membership");
                }
            }

        }

        return $this->paywalledEntitiesRepository->mapMembershipsToEntity((int) $entity->getGuid(), $membershipGuids);
    }

}
