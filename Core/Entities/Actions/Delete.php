<?php

/**
 * Minds Entities Delete action
 *
 * @author Mark
 */

namespace Minds\Core\Entities\Actions;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\ACL;

class Delete
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    /** @var mixed */
    protected $entity;

    /**
     * Save constructor.
     * @param null $eventsDispatcher
     */
    public function __construct(
        $eventsDispatcher = null,
        private ?EntitiesRepositoryInterface $entitiesRepository = null,
        private ?ACL $acl = null,
    ) {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->entitiesRepository ??= Di::_()->get(EntitiesRepositoryInterface::class);
        $this->acl ??= Di::_()->get(ACL::class);
    }

    /**
     * Sets the entity
     * @param mixed $entity
     * @return Delete
     */
    public function setEntity($entity): Delete
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Delete the entity
     * @return bool
     * @throws \Minds\Exceptions\StopEventException
     */
    public function delete()
    {
        if (!$this->entity) {
            return false;
        }

        if (!$this->acl->write($this->entity)) {
            throw new UnauthorizedException();
        }

        $delete = $this->eventsDispatcher->trigger('delete', $this->entity->getType(), [ 'entity' => $this->entity ]);

        $success = $delete && $this->entitiesRepository->delete($this->entity);

        if ($success) {
            $this->eventsDispatcher->trigger('entities-ops', 'delete', [
                'entityUrn' => $this->entity->getUrn(),
                'entity' => $this->entity,
            ]);
        }

        return $success;

        // TODO: remove after here

        $namespace = $this->entity->type;

        if ($this->entity->subtype) {
            $namespace .= ":{$this->entity->subtype}";
        }

        return $this->eventsDispatcher->trigger('entity:delete', $namespace, [
            'entity' => $this->entity,
        ], false);
    }
}
