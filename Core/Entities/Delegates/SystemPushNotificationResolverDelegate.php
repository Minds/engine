<?php

namespace Minds\Core\Entities\Delegates;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Notifications\Push\System\Manager;
use Minds\Core\Notifications\Push\System\Models\AdminPushNotificationRequest;

/**
 *
 */
class SystemPushNotificationResolverDelegate implements ResolverDelegate
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
    }

    /**
     * @inheritDoc
     */
    public function shouldResolve(Urn $urn): bool
    {
        return $urn->getNid() === 'system-push-notification';
    }

    /**
     *
     * @return AdminPushNotificationRequest[]|null
     * @throws Exception
     */
    public function resolve(array $urns, array $opts = []): ?array
    {
        $entities = [];

        foreach ($urns as $urn) {
            $entities[] = $this->manager->getRequestByUrn($urn);
        }

        return $entities;
    }

    /**
     * @inheritDoc
     */
    public function map($urn, $entity)
    {
        return $entity;
    }

    /**
     * @param AdminPushNotificationRequest $entity
     * @return string|null
     */
    public function asUrn($entity): ?string
    {
        return $entity?->getUrn();

    }
}
