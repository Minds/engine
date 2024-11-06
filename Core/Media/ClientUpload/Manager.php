<?php
/**
 * Client Upload, direct from browser to storage
 */
namespace Minds\Core\Media\ClientUpload;

use DateTimeImmutable;
use Minds\Common\Access;
use Minds\Core\Media\Video\Transcoder;
use Minds\Core\Media\Video\Manager as VideoManager;
use Minds\Core\GuidBuilder;
use Minds\Core\Di\Di;
use Minds\Core\Media\Audio\AudioEntity;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;
use Minds\Entities\User;
use Minds\Entities\Video;

class Manager
{
    public function __construct(
        private readonly Transcoder\Manager $transcoderManager,
        private readonly VideoManager $videoManager,
        private readonly GuidBuilder $guid,
        private readonly RbacGatekeeperService $rbacGatekeeperService,
        private readonly AudioService $audioService,
    ) {
    }

    /**
     * Prepare an upload, return a lease
     * @param MediaTypeEnum $type - the media type
     * @return ClientUploadLease
     */
    public function prepare(MediaTypeEnum $type = MediaTypeEnum::VIDEO, User $user)
    {
        switch ($type) {
            case MediaTypeEnum::VIDEO:
                // Do not allow video uploads
                $this->rbacGatekeeperService->isAllowed(PermissionsEnum::CAN_UPLOAD_VIDEO);

                $video = new Video();
                $video->set('guid', $this->guid->build());

                $preSignedUrl = $this->transcoderManager->getClientSideUploadUrl($video);

                $lease = new ClientUploadLease(
                    guid: $video->getGuid(),
                    mediaType: $type,
                    presignedUrl: $preSignedUrl,
                );

                return $lease;
                break;
            case MediaTypeEnum::AUDIO:
                // Check if the site allows audio uploads (user level)
                $this->rbacGatekeeperService->isAllowed(PermissionsEnum::CAN_UPLOAD_AUDIO);

                $audio = new AudioEntity(
                    guid: (int) $this->guid->build(),
                    ownerGuid: (int) $user->getGuid(),
                    accessId: Access::UNLISTED, // Hide until published
                );

                $this->audioService->onUploadInitiated($audio);

                $preSignedUrl = $this->audioService->getClientSideUploadUrl($audio);

                $lease = new ClientUploadLease(
                    guid: $audio->guid,
                    mediaType: $type,
                    presignedUrl: $preSignedUrl,
                );

                return $lease;

                break;
            default:
                throw new \Exception("$type is not currently supported for client based uploads");
        }
    }

    /**
     * Complete the client based upload
     * @param ClientUploadLease $lease
     * @return boolean
     */
    public function complete(ClientUploadLease $lease, User $user)
    {
        switch ($lease->mediaType) {
            case MediaTypeEnum::VIDEO:
                $video = new Video();
                $video->set('guid', $lease->guid);
                $video->set('owner_guid', $user->getGuid());
                $video->set('cinemr_guid', $lease->guid);
                $video->set('access_id', 0); // Hide until published
                $video->setFlag('full_hd', !!$user->isPro());
        
                $video->setTranscoder('cloudflare');
        
                $this->videoManager->add($video);
                break;
            case MediaTypeEnum::AUDIO:

                // Get the audio entity
                $audio = $this->audioService->getByGuid($lease->guid);

                // $audio = new AudioEntity(
                //     guid: $lease->guid,
                //     ownerGuid: $user->getGuid(),
                    
                // );

                $this->audioService->onUploadCompleted($audio, $user);
                break;
            default:
                throw new \Exception("{$lease->mediaType} is not currently supported for client based uploads");
        }

        return true;
    }
}
