<?php

namespace Minds\Core\Entities;

use Minds\Core\Data\Call;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;

/**
 * GuidLinkResolver.
 * Given an entity_guid, will resolve to return linked Activity guid,
 * or vice-verse.
 */
class GuidLinkResolver
{
    /**
     * Constructor.
     * @param ?EntitiesBuilder $entitiesBuilder - to build entities.
     * @param ?Call $db - db call.
     */
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Call $db = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->db ??= new Call('entities_by_time');
    }

    /**
     * Gets linked GUID for entity. For example if given an Activity guid, should
     * return an entity_guid. Else if given an entity_guid, should return a linked
     * Activity guid.
     * @param string $guid - guid to get linked entity guid for.
     * @return ?string - linked entity guid.
     */
    public function resolve(string $guid): ?string
    {
        // Lookup entity link so an entity guid should return a linked Activity guid.
        // Note: entities made post multi-image will have their activity stored as their container guid.
        $linkedActivities = $this->db->getRow("activity:entitylink:{$guid}");

        if (count($linkedActivities)) {
            return array_values($linkedActivities)[0];
        }
        // Else try building the guid. Will get the guid of an entity from an Activity guid.
        $entity = $this->entitiesBuilder->single($guid);
        return $entity->getEntityGuid() ?? null;
    }

    /**
     * Unidirectional resolver for getting an Activity object by a given Entity guid.
     * Will return null in the event that no linked Activity is found.
     * @param string $entityGuid - guid to get for.
     * @return Activity|null linked activity, or null in the event no activity is found.
     */
    public function resolveActivityFromEntityGuid(string $entityGuid): ?Activity
    {
        $linkedActivities = $this->db->getRow("activity:entitylink:{$entityGuid}");

        if (!count($linkedActivities)) {
            return null;
        }

        $activityGuid = array_values($linkedActivities)[0];
        $activity = $this->entitiesBuilder->single($activityGuid);
        return ($activity && $activity instanceof Activity) ?
            $activity :
            null;
    }
}
