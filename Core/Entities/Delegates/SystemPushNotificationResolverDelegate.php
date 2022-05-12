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
    }

    /**
     * @inheritDoc
     */
    public function shouldResolve(Urn $urn): bool
    {
        return $urn->getNid() === AdminPushNotificationRequest::URN_METHOD;
    }

    /**
     *
     * @return AdminPushNotificationRequest[]|null
     * @throws Exception
     */
    public function resolve(array $urns, array $opts = []): ?array
    {
        $entities = [];

        $this->getManager();
        foreach ($urns as $urn) {
            try {
                $entities[] = $this->manager->getRequestByUrn($urn);
            } catch (Exception $e) {
                continue;
            }
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

    /**
     * @return Manager
     */
    public function getManager(): Manager
    {
        if (!$this->manager) {
            $this->manager = new Manager();
        }
        return $this->manager;
    }
}
