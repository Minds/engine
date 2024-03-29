<?php
/**
 * GuidResolverDelegate.
 *
 * @author emi
 */

namespace Minds\Core\Entities\Delegates;

use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\Entities as TopEntities;
use Minds\Entities\EntityInterface;

class EntityGuidResolverDelegate implements ResolverDelegate
{
    /**
     * @var EntitiesBuilder
     */
    protected $entitiesBuilder;

    /**
     * EntityGuidResolverDelegate constructor.
     * @param EntitiesBuilder $entitiesBuilder
     */
    public function __construct($entitiesBuilder = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param Urn $urn
     * @return boolean
     */
    public function shouldResolve(Urn $urn): bool
    {
        return in_array($urn->getNid(), [
                'entity',
                'activity',
                'image',
                'video',
                'blog',
                'user',
                'group',
            ], true);
    }

    /**
     * @param array $urns
     * @param array $opts
     * @return mixed
     */
    public function resolve(array $urns, array $opts = []): ?array
    {
        $opts = array_merge([
            'asActivities' => false,
        ], $opts);

        if (!$urns) {
            return [];
        }

        $guids = array_map(function (Urn $urn) {
            return $urn->getNss();
        }, $urns);

        $entities = [];

        foreach ($guids as $guid) {
            $entity = $this->entitiesBuilder->single($guid, $opts);

            if ($entity && $entity instanceof EntityInterface) {
                $entities[] = $entity;
            }
        }

        // Map, if faux-Activities are needed
        if ($opts['asActivities']) {
            /** @var TopEntities $entities */
            $topEntities = new TopEntities();

            // Cast to ephemeral Activity entities, if another type
            $entities = array_map([$topEntities, 'cast'], $entities);
        }

        return $entities;
    }

    /**
     * @param $urn
     * @param mixed $entity
     * @return mixed
     */
    public function map($urn, $entity)
    {
        // NOTE: No need to attach URN as GUID fallback defaults to this delegate
        return $entity;
    }

    /**
     * @param mixed $entity
     * @return string|null
     */
    public function asUrn($entity): ?string
    {
        if (!$entity) {
            return null;
        }

        if (method_exists($entity, 'getUrn') && $entity->getUrn()) {
            return $entity->getUrn();
        }

        if (method_exists($entity, '_magicAttributes') || method_exists($entity, 'getGuid')) {
            return "urn:entity:{$entity->getGuid()}";
        } elseif (isset($entity->guid)) {
            return "urn:entity:{$entity->guid}";
        }

        return null;
    }
}
