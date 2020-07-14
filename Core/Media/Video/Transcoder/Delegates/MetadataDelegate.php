<?php
namespace Minds\Core\Media\Video\Transcoder\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Entities\Video;
use Minds\Core\EntitiesBuilder;

class MetadataDelegate
{
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct($entitiesBuilder = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Stores the width and height of the video
     * @param Transcode $transcode
     * @return void
     */
    public function onThumbnailsCompleted(Transcode $transcode): void
    {
        $video = $this->entitiesBuilder->single($transcode->getGuid());
        if (!$video || !$video instanceof Video) {
            error_log("Video ({$transcode->getGuid()}not found");
            return; // TODO: Tell sentry?
        }

        $video->width = $transcode->getProfile()->getWidth();
        $video->height = $transcode->getProfile()->getHeight();

        $video->save();

        if ($video->activity_guid) {
            $activity = $this->entitiesBuilder->single($video->activity_guid);
            $custom = $activity->custom_data;
            $custom['width'] = $video->width;
            $custom['height'] = $video->height;
            $activity->custom_data = $custom;
            $activity->save();
        }
    }
}
