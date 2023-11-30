<?php
/**
 * Client Upload, direct from browser to storage
 */
namespace Minds\Core\Media\ClientUpload;

use Minds\Core\Media\Video\Transcoder;
use Minds\Core\Media\Video\Manager as VideoManager;
use Minds\Core\GuidBuilder;
use Minds\Core\Di\Di;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;
use Minds\Entities\Video;

class Manager
{
    /** @var Transcoder\Manager */
    private $transcoderManager;

    /** @var VideoManager */
    private $videoManager;

    /** @var Guid $guid */
    private $guid;

    public function __construct(
        Transcoder\Manager $transcoderManager = null,
        VideoManager $videoManager = null,
        GuidBuilder $guid = null,
        private ?RbacGatekeeperService $rbacGatekeeperService = null,
    ) {
        $this->transcoderManager = $transcoderManager ?? Di::_()->get('Media\Video\Transcoder\Manager');
        $this->videoManager = $videoManager ?: Di::_()->get('Media\Video\Manager');
        $this->guid = $guid ?: new GuidBuilder();
        $this->rbacGatekeeperService ??= Di::_()->get(RbacGatekeeperService::class);
    }

    /**
     * Prepare an upload, return a lease
     * @param $type - the media type
     * @return ClientUploadLease
     */
    public function prepare($type = 'video')
    {
        if ($type != 'video') {
            throw new \Exception("$type is not currently supported for client based uploads");
        }

        // Do not allow video uploads
        $this->rbacGatekeeperService->isAllowed(PermissionsEnum::CAN_UPLOAD_VIDEO);

        $video = new Video();
        $video->set('guid', $this->guid->build());

        $preSignedUrl = $this->transcoderManager->getClientSideUploadUrl($video);

        $lease = new ClientUploadLease();
        $lease->setGuid($video->getGuid())
            ->setMediaType($type)
            ->setPresignedUrl($preSignedUrl);

        return $lease;
    }

    /**
     * Complete the client based upload
     * @param ClientUploadLease $lease
     * @return boolean
     */
    public function complete(ClientUploadLease $lease)
    {
        if ($lease->getMediaType() !== 'video') {
            throw new \Exception("{$lease->getMediaType()} is not currently supported for client based uploads");
        }

        $video = new Video();
        $video->set('guid', $lease->getGuid());
        $video->set('owner_guid', $lease->getUser()->getGuid());
        $video->set('cinemr_guid', $lease->getGuid());
        $video->set('access_id', 0); // Hide until published
        $video->setFlag('full_hd', !!$lease->getUser()->isPro());

        $video->setTranscoder('cloudflare');

        $this->videoManager->add($video);

        return true;
    }
}
