<?php
/**
 * Client Upload, direct from browser to storage
 */
namespace Minds\Core\Media\ClientUpload;

use Minds\Core\Media\Services\FFMpeg;
use Minds\Core\GuidBuilder;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Di\Di;
use Minds\Entities\Video;

class Manager
{
    /** @var FFMpeg */
    private $ffmpeg;

    /** @var Guid $guid */
    private $guid;

    /** @var bool */
    private $full_hd;

    /** @var Save $save */
    private $save;


    /**
     * @param bool $value
     * @return Manager
     */
    public function setFullHD(bool $value): Manager
    {
        $this->full_hd = $value;
        return $this;
    }

    public function __construct(
        FFMpeg $FFMpeg = null,
        GuidBuilder $guid = null,
        Save $save = null
    ) {
        $this->ffmpeg = $FFMpeg ?: Di::_()->get('Media\Services\FFMpeg');
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

        $guid = $this->guid->build();

        $this->ffmpeg->setKey($guid);
        $preSignedUrl = $this->ffmpeg->getPresignedUrl();

        $lease = new ClientUploadLease();
        $lease->setGuid($guid)
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
        $video->setFlag('full_hd', $this->full_hd);

        // Save the video
        $this->save->setEntity($video)->save();

        $this->ffmpeg->setKey($lease->getGuid());

        // Set the full hd flag
        $this->ffmpeg->setFullHD($this->full_hd);

        // Start the transcoding process
        $this->ffmpeg->transcode();

        return true;
    }
}
