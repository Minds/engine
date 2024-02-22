<?php
namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services;

use Minds\Core\Guid;
use Minds\Core\Media\BlurHash;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\PaywalledEntitiesRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Entities\Activity;
use Minds\Entities\File;
use Minds\Exceptions\UserErrorException;

class CreatePaywalledEntityService
{
    public function __construct(
        private PaywalledEntitiesRepository $paywalledEntitiesRepository,
        private SiteMembershipReaderService $siteMembershipReaderService,
        private ImagickManager $imagickManager,
        private BlurHash $blurHash,
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

    /**
     * Uploads a paywall poster for an activity post
     */
    public function processPaywallThumbnail(Activity $activity, string  $blob): bool
    {
        $blobParts = explode(',', $blob);

        if (!isset($blobParts[1])) {
            throw new UserErrorException("Invalid image type");
        }

        $blob = $blobParts[1];

        $blob = base64_decode($blob, true);

        $imageData = $this->imagickManager
            ->setImageFromBlob($blob)
            ->getJpeg();

        $file = new File();
        $file->setFilename("paywall_thumbnails/{$activity->getGuid()}.jpg");
        $file->owner_guid = $activity->getOwnerGuid();
        $file->open('write');
        $file->write($imageData);
        $file->close();

        $dimensions = $this->imagickManager->getImagick()->getImageGeometry();

        $activity->setPaywallThumbnail(
            width: $dimensions['width'],
            height: $dimensions['height'],
            blurhash: $this->blurHash->getHash($imageData)
        );

        return true;
    }

}
