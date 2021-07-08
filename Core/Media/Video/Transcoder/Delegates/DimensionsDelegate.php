<?php
namespace Minds\Core\Media\Video\Transcoder\Delegates;

use FFMpeg\Coordinate\Dimension;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles\Source;

/**
 * Handles the reprocessing of a videos dimensions.
 */
class DimensionsDelegate
{
    /** @var Media\Services\FFMpeg */
    private $ffmpeg;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var ACL */
    private $acl;

    /** @var Save */
    private $save;

    /** @var Logger */
    private $logger;

    /** @var Media\Video\Manager */
    private $videoManager;

    /** @var TranscodeStorageInterface */
    private $transcodeStorage;

    public function __construct(
        $ffmpeg = null,
        $entitiesBuilder = null,
        $acl = null,
        $save = null,
        $logger = null,
        $transcodeStorage = null,
        $videoManager = null
    ) {
        $this->ffmpeg = $ffmpeg ?? Di::_()->get('Media\Services\FFMpeg');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->acl = $acl ?? Di::_()->get('Security\ACL');
        $this->save = $save ?? new Save();
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->videoManager = $videoManager ?: Di::_()->get('Media\Video\Manager');
        $this->transcodeStorage = $transcodeStorage ?? Di::_()->get('Media\Video\Transcode\TranscodeStorage');
    }

    /**
     * Reprocess a videos dimensions.
     *
     * @param string guid - the guid of the video entity
     * @param string url - optional dev param to override URL.
     * @return bool - true if result was saved.
     */
    public function reprocess($guid, $url = ''): Dimension
    {
        if (!$guid) {
            $this->logger->error("[DimensionsDelegate]: No video guid provided");
            return false;
        }

        $video = $this->videoManager->get($guid);

        if (!$video) {
            throw new \Exception("[DimensionsDelegate]: {$guid} not found");
        }

        // Download the source, allow dev param to override url.
        if (!$url) {
            $source = new Transcode();
            $source
                ->setVideo($video)
                ->setProfile(new Source());
            
            try {
                $url = $this->transcodeStorage->downloadToTmp($source);
            } catch (\Exception $e) {
                $this->logger->error('[DimensionsDelegate]: '.$e->getMessage());
                throw new \Exception("[DimensionsDelegate]: Error downloading {$video->getGuid()} from storage");
            }
        }
 
        $videoDimensions = $this->ffmpeg->getDimensions($url);

        $width = $videoDimensions->getWidth();
        $height = $videoDimensions->getHeight();

        $video['height'] = $height;
        $video['width'] = $width;

        $ia = $this->acl->setIgnore(true);

        $success = $this->save
            ->setEntity($video)
            ->save(true);

        $this->acl->setIgnore($ia); // Set the ignore state back to what it was

        if ($success) {
            $this->logger->info("[DimensionsDelegate]: Saved with dimensions h($height), w($width)");
            return $videoDimensions;
        }
        return false;
    }
}
