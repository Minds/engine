<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\Clicks;

use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Analytics\Clicks\Delegates\ActionEventsDelegate as ClickActionEventsDelegate;
use Minds\Core\Analytics\Clicks\Delegates\PostHogDelegate;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

/**
 * Manages logic around the handling of clicks.
 */
class Manager
{
    public function __construct(
        private ?ClickActionEventsDelegate $actionEventsDelegate = null,
        private ?PostHogDelegate $postHogDelegate = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?EntitiesResolver $entitiesResolver = null
    ) {
        $this->actionEventsDelegate ??= Di::_()->get(ClickActionEventsDelegate::class);
        $this->postHogDelegate ??= Di::_()->get(PostHogDelegate::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->entitiesResolver ??= Di::_()->get(EntitiesResolver::class);
    }

    /**
     * Track a click.
     * @param string $entityGuid - guid of entity we are tracking for.
     * @param array $clientMeta - additional client meta information.
     * @param User $user - user who triggered the click.
     * @throws NotFoundException - if a valid entity is not found for given identifier.
     * @return void
     */
    public function trackClick(string $entityGuid, array $clientMeta, ?User $user = null): void
    {
        $campaign = $clientMeta['campaign'] ?? null;

        $entity = $campaign ?
            $this->getEntityByUrn($campaign) :
            $this->getEntityByGuid($entityGuid);

        $this->actionEventsDelegate->onClick($entity, $user);
        if ($user) {
            $this->postHogDelegate->onClick($entity, $clientMeta, $user);
        }
    }

    /**
     * Gets an entity by guid.
     * @param string $entityGuid - guid to get entity by.
     * @throws NotFoundException - if a valid entity is not found.
     * @return EntityInterface - entity.
     */
    private function getEntityByGuid(string $entityGuid): EntityInterface
    {
        if (!$entity = $this->entitiesBuilder->single($entityGuid)) {
            throw new NotFoundException("No entity found for identifier: $entityGuid");
        }
        if (!($entity instanceof EntityInterface)) {
            throw new NotFoundException("Invalid entity found for identifier: $entityGuid");
        }
        return $entity;
    }

    /**
     * Gets an entity by URN.
     * @param string $urn - urn to get entity by.
     * @throws NotFoundException - if a valid entity is not found.
     * @return EntityInterface - entity.
     */
    private function getEntityByUrn(string $urn): EntityInterface
    {
        if (!$entity = $this->entitiesResolver->single(new Urn($urn))) {
            throw new NotFoundException("No Boost found for identifier: $urn");
        }
        if (!($entity instanceof EntityInterface)) {
            throw new NotFoundException("Invalid entity found for identifier: $urn");
        }
        return $entity;
    }
}
