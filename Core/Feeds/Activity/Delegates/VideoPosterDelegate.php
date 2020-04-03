<?php
namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Translation\Storage;
use Minds\Entities\Entity;
use Minds\Entities\Activity;
use Minds\Entities\Factory;
use Minds\Common\EntityMutation;
use Minds\Core\Media\Assets;
use Minds\Helpers;

class VideoPosterDelegate
{
    /** @var Assets\Video */
    private $videoAssets;

    public function __construct($videoAssets = null)
    {
        $this->videoAssets = $videoAssets ?? new Assets\Video;
    }

    /**
     * Clears the translation storage on update
     * @param Entity $entity
     * @param EntityMutation $activityMutation
     * @return void
     */
    public function onUpdate(Entity $entity, EntityMutation $entityMutation): void
    {
        $assets = $this->videoAssets
            ->setDoSave(false)
            ->setEntity($activity)
            ->update([ 'file' => $activity->getVideoPosterBase64Blob() ]);

        $thumbnail = $assets['thumbnail'];

        $custom = $activity->getCustom();
        $custom['thumbnail_src'] = $thumbnail;
        $entityMutation->setCustom($custom);
    }
}
