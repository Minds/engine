<?php

namespace Minds\Core\Entities;

use Minds\Core\Data\Call;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\Propagator\Properties;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Core;

/**
 * Class PropagateProperties
 * @package Minds\Core\Entities
 */
class PropagateProperties
{
    /**  @var Properties[] */
    protected $propagators;
    /** @var Call */
    private $db;
    /** @var Save */
    private $save;
    /** @var EntitiesBuilder */
    private $entitiesBuilder;
    /** @var bool */
    private $changed = false;

    /**
     * PropagateProperties constructor.
     * @param Call|null $db
     * @param Save|null $save
     * @param EntitiesBuilder|null $entitiesBuilder
     */
    public function __construct(Call $db = null, Save $save = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->db = $db ?? new Call('entities_by_time');
        $this->save = $save ?? new Save();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->registerPropagators();
    }

    /**
     * Register PropagateProperties classes
     * @throws \Exception
     */
    protected function registerPropagators(): void
    {
        $this->addPropagator(Core\Blogs\Delegates\PropagateProperties::class);
        $this->addPropagator(Core\Feeds\Delegates\PropagateProperties::class);
        $this->addPropagator(Core\Media\Delegates\PropagateProperties::class);
        $this->addPropagator(Core\Entities\Delegates\PropagateProperties::class);
        $this->addPropagator(Core\Permissions\Delegates\PropagateProperties::class);
    }

    public function clearPropogators(): void
    {
        $this->propagators = [];
    }

    /**
     * Add a propagator to be called in the chain
     * @param string $class
     * @throws \Exception
     */
    protected function addPropagator(string $class): void
    {
        $obj = new $class();
        if (!$obj instanceof Properties) {
            throw new \Exception('Propagator class is not a Property Propagator');
        }

        $this->propagators[] = $obj;
    }

    /**
     * Propagate the properties from the passed entity
     * @param $entity
     */
    public function from($entity): void
    {
        if ($entity instanceof Activity) {
            $this->fromActivity($entity);
        } else {
            $this->toActivities($entity);
        }
    }

    /**
     * Propagate properties from an Activity to to it's attachment
     * @param Activity $activity
     * @throws \Minds\Exceptions\StopEventException
     * @throws \Exception
     */
    protected function fromActivity(Activity $activity): void
    {
        $this->changed = false;
        $attachment = $this->entitiesBuilder->single($activity->get('entity_guid'));
        if (!$attachment) {
            return;
        }

        foreach ($this->propagators as $propagator) {
            if ($propagator->willActOnEntity($attachment)) {
                $attachment = $propagator->fromActivity($activity, $attachment);
                $this->changed |= $propagator->changed();
                if (!is_object($attachment)) {
                    throw new \Exception(get_class($propagator) . ' fromActivity method did not return a modified object');
                }
            }
        }

        if ($this->changed) {
            $this->save->setEntity($attachment)->save();
        }
    }

    /**
     * Propagate properties from an Entity to it's activities
     * @param $entity
     * @throws \Minds\Exceptions\StopEventException
     */
    protected function toActivities($entity): void
    {
        $activities = $this->getActivitiesForEntity($entity->getGuid());
        foreach ($activities as $activity) {
            $this->propagateToActivity($entity, $activity);
            if ($this->changed) {
                $this->save->setEntity($activity)->save();
            }
        }
    }

    /**
     * Get activities for an entity
     * @param string $entityGuid
     * @return Activity[]
     */
    private function getActivitiesForEntity(string $entityGuid): array
    {
        $activities = [];

        foreach ($this->db->getRow("activity:entitylink:{$entityGuid}") as $activityGuid => $ts) {
            $activities[] = $this->entitiesBuilder->single($activityGuid);
        }

        return $activities;
    }

    /**
     * Propagate properties from and entity to an activity
     * @param $entity
     * @param Activity $activity
     */
    public function propagateToActivity($entity, Activity &$activity): void
    {
        $this->changed = false;
        foreach ($this->propagators as $propagator) {
            if ($propagator->willActOnEntity($entity)) {
                $activity = $propagator->toActivity($entity, $activity);
                $this->changed |= $propagator->changed();
            }
        }
    }
}
