<?php
namespace Minds\Core\Media\Video\Transcoder\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;

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

    public function __construct(
        $ffmpeg = null,
        $entitiesBuilder = null,
        $acl = null,
        $save = null,
        $logger = null
    ) {
        $this->ffmpeg = $ffmpeg ?? Di::_()->get('Media\Services\FFMpeg');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->acl = $acl ?? Di::_()->get('Security\ACL');
        $this->save = $save ?? new Save();
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    /**
     * Reprocess a videos dimensions.
     *
     * @param string guid - the guid of the video entity
     * @param string url - optional dev param to override URL.
     * @return bool - true if result was saved.
     */
    public function reprocess($guid, $url = ''): bool
    {
        if (!$guid) {
            $this->logger->error("No video guid provided");
            return false;
        }

        $video = $this->entitiesBuilder->single($guid);

        // override URL for development purposes.
        if (!$url) {
            $url = $video->getSourceUrl('source');
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
            $this->logger->info("Saved with dimensions h($height), w($width)");
            return true;
        }
        return false;
    }
}
