<?php
namespace Minds\Core\Media\Video\Transcoder\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Entities\Video;
use Minds\Entities\Activity;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\Call;
use Minds\Core\Entities\Actions\Save;

class MetadataDelegate
{
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Call */
    private $db;

    /** @var Save */
    private $save;

    /**
     * Constructor.
     * @param EntitiesBuilder|null $entitiesBuilder
     * @param Call|null $db
     * @param Save|null $save
     */
    public function __construct($entitiesBuilder = null, Call $db = null, Save $save = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->db = $db ?? new Call('entities_by_time');
        $this->save = $save ?? new Save();
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
            error_log("Video ({$transcode->getGuid()}) not found or not a valid source");
            return; // TODO: Tell sentry?
        }

        $video->width = $transcode->getProfile()->getWidth();
        $video->height = $transcode->getProfile()->getHeight();

        $video->save();

        $activities = $this->db->getRow("activity:entitylink:{$video->getGuid()}");

        if (!empty($activities)) {
            foreach ($activities as $guid) {
                $activity = $this->entitiesBuilder->single($guid);
                if (!$activity || !$activity instanceof Activity) {
                    continue;
                }
                $custom = $activity->custom_data;
                $custom['width'] = $video->width;
                $custom['height'] = $video->height;
                $activity->custom_data = $custom;
                $this->save
                    ->setEntity($activity)
                    ->save();
            }
        }
    }
}
