<?php
namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Translation\Storage;
use Minds\Entities\Activity;
use Minds\Entities\Factory;
use Minds\Common\EntityMutation;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\PropagateProperties;

class ForeignEntityDelegate
{
    /** @var Storage */
    private $translationStorage;

    /** @var Save */
    private $save;

    /** @var PropagateProperties */
    private $propagateProperties;

    public function __construct($save = null, $propagateProperties = null)
    {
        $this->save = $save ?? new Save();
        $this->propagateProperties = $propagateProperties ?? new PropagateProperties();
    }

    /**
     * Clears the translation storage on update
     * @param Activity $activity
     * @param EntityMutation $activityMutation
     * @return void
     */
    public function onUpdate(Activity $activity, EntityMutation $entityMutation): void
    {
        $entity = Factory::build($activity);

        foreach ($entityMutation->getMutatedValues() as $var => $value) {
            $setterName = 'set' . ucfirst($var);
            $entity->$setterName($value); // TODO: check if it exists first
        }

        $this->save
            ->setEntity($entity)
            ->save();

        $this->propagateProperties->from($entity);
    }
}
