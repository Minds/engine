<?php
namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Translation\Storage;
use Minds\Entities\Activity;
use Minds\Entities\Factory;
use Minds\Common\EntityMutation;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\Media\Assets;
use Minds\Helpers;

class ForeignEntityDelegate
{
    /** @var Save */
    private $save;

    /** @var PropagateProperties */
    private $propagateProperties;

    /** @var Assets\Video */
    private $videoAssets;

    public function __construct($save = null, $propagateProperties = null, $videoAssets = null)
    {
        $this->save = $save ?? new Save();
        $this->propagateProperties = $propagateProperties ?? new PropagateProperties();
        $this->videoAssets = $videoAssets ?? new Assets\Video;
    }

    /**
     * Clears the translation storage on update
     * @param Activity $activity
     * @param EntityMutation $activityMutation
     * @return void
     */
    public function onUpdate(Activity $activity, EntityMutation $entityMutation): void
    {
        $entity = Factory::build((object) $activity);

        foreach ($entityMutation->getMutatedValues() as $var => $value) {
            $setterName = 'set' . ucfirst($var);
            if (!Helpers\MagicAttributes::setterExists($entity, $setterName)) {
                continue; // Allow to fail as this is only a delegate
            }
            $entity->$setterName($value); // TODO: check if it exists first
        }

        if ($entityMutation->hasMutated('videoPosterBase64Blob')) {
            $assets = $this->videoAssets
                ->setDoSave(false)
                ->setEntity($entity)
                ->update([ 'file' => $activity->getVideoPosterBase64Blob() ]);
            $entity->setAssets($assets);
            $entity->set('last_updated', time());
        }

        $this->save
            ->setEntity($entity)
            ->save();

        $this->propagateProperties->from($entity);
    }
}
