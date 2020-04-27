<?php
namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Translation\Storage;
use Minds\Entities\Entity;
use Minds\Entities\Activity;
use Minds\Entities\Factory;
use Minds\Core\Media\Assets;
use Minds\Core\Di\Di;
use Minds\Helpers;

class VideoPosterDelegate
{
    /** @var Assets\Video */
    private $videoAssets;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Save */
    private $save;

    public function __construct($videoAssets = null, $entitiesBuilder = null)
    {
        $this->videoAssets = $videoAssets ?? new Assets\Video;
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * On adding the activity
     * @param Activity $activity
     * @return void
     */
    public function onAdd(Activity $activity): void
    {
        $this->updateThumbnails($activity);
        // Cleanup the base64 poster as we won't use it again
    }

    /**
     * Clears the translation storage on update
     * @param Activity $activity
     * @return void
     */
    public function onUpdate(Activity $activity): void
    {
        $this->updateThumbnails($activity);
    }

    /**
     * Upload the poster
     * @param Activity $activity
     * @return void
     */
    private function updateThumbnails(Activity $activity): void
    {
        list($customType, $customData) = $activity->getCustom();
        if ($customType !== 'video') {
            return;
        }
        $video = $this->entitiesBuilder->single($activity->getEntityGuid());
        $this->videoAssets
            ->setDoSave(true)
            ->setEntity($video)
            ->update([ 'file' => $activity->getVideoPosterBase64Blob() ]);
        $activity->setCustom(...$video->getActivityParameters());
        // Cleanup the base64 poster as we won't use it again
        $activity->setVideoPosterBase64Blob('');
    }
}
