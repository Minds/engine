<?php
/**
 * Client Upload, direct from browser to storage
 */
namespace Minds\Core\Media\ClientUpload;

use Minds\Core\Media\Video\Transcoder\Manager as TranscoderManager;
use Minds\Core\GuidBuilder;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Di\Di;
use Minds\Entities\Video;

class Manager
{
    /** @var TranscoderManager */
    private $transcoderManager;

    /** @var Guid $guid */
    private $guid;

    /** @var Save $save */
    private $save;


    public function __construct(
        TranscoderManager $transcoderManager = null,
        GuidBuilder $guid = null,
        Save $save = null
    ) {
        $this->transcoderManager = $transcoderManager ?: Di::_()->get('Media\Video\Transcoder\Manager');
        $this->guid = $guid ?: new GuidBuilder();
        $this->save = $save ?: new Save();
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
        $video->set('cinemr_guid', $lease->getGuid());
        $video->set('access_id', 0); // Hide until published
        $video->setFlag('full_hd', !!$lease->getUser()->isPro());

        // Save the video
        $this->save->setEntity($video)->save();

        // Kick off the transcoder
        $this->transcoderManager->createTranscodes($video);

        return true;
    }
}
