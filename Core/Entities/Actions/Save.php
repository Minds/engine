<?php

/**
 * Minds Entities Save action.
 *
 * @author emi
 */

namespace Minds\Core\Entities\Actions;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\Dispatcher;
use Minds\Core\EventStreams\UndeliveredEventException;
use Minds\Core\Guid;
use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\ACL;
use Minds\Exceptions\StopEventException;
use Minds\Helpers\MagicAttributes;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Entities\EntityInterface;
use Minds\Entities\Factory;

/**
 * Save Action
 */
class Save
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    /** @var mixed */
    protected $entity;

    /** @var Logger */
    protected $logger;

    /** @var string[] */
    protected $mutatedAttributes = [];

    /**
     * Save constructor.
     *
     * @param null $eventsDispatcher
     */
    public function __construct(
        $eventsDispatcher = null,
        $logger = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?EntitiesRepositoryInterface $entitiesRepository = null,
        private ?ACL $acl = null,
    ) {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->logger = $logger ?: Di::_()->get('Logger');
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
        $this->entitiesRepository ??= Di::_()->get(EntitiesRepositoryInterface::class);
        $this->acl ??= Di::_()->get(ACL::class);
    }

    /**
     * Sets the entity.
     *
     * @param mixed $entity
     *
     * @return Save
     */
    public function setEntity(EntityInterface $entity): Save
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Gets the set entity.
     *
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * If you wish only update part of the entity, then pass through the mutated
     * attributes with this function
     */
    public function withMutatedAttributes(array $mutatedAttributes): Save
    {
        $instance = clone $this;
        $instance->mutatedAttributes = $mutatedAttributes;
        return $instance;
    }

    /**
     * Saves the entity.
     * @return bool
     * @throws StopEventException
     * @throws UnverifiedEmailException
     */
    public function save(bool $isUpdate = null)
    {
        $success = false;

        if (!$this->entity) {
            return false;
        }

        if (!$this->acl->write($this->entity)) {
            throw new UnauthorizedException();
        }

        $this->beforeSave();

        //

        if ($isUpdate === null) {
            if ($this->entity->getGuid()) {
                // Ambigous if we should update or create. Perform a SELECT query to see if the entity exists
                $isUpdate = !!$this->entitiesRepository->loadFromGuid($this->entity->getGuid());
            } else {
                $isUpdate = false;
            }
        }

        if ($isUpdate) {
            $this->eventsDispatcher->trigger('update', 'elgg/event/' . $this->entity->getType(), $this->entity);
        } else {
            if (!$this->entity->getGuid()) {
                $this->entity->guid = Guid::build();
            }
            $this->eventsDispatcher->trigger('create', 'elgg/event/' .  $this->entity->getType(), $this->entity);
        }

        if ($isUpdate) {
            $success = $this->entitiesRepository->update(
                entity: $this->entity,
                columns: $this->mutatedAttributes
            );
        } else {
            $success = $this->entitiesRepository->create($this->entity);
        }

        try {
            $this->eventsDispatcher->trigger('entities-ops', $isUpdate ? 'update' : 'create', [
                'entityUrn' => $this->entity->getUrn()
            ]);
        } catch (UndeliveredEventException $e) {
            if (!$isUpdate) {
                // This is a new entity, so we will delete it
                $this->entitiesRepository->delete($this->entity);
            }
            // Rethrow
            throw $e;
        }

        // Invalidate the cache
        Factory::invalidateCache($this->entity);

        $namespace = $this->entity->getType();

        if ($this->entity->getSubtype()) {
            $namespace .= ":{$this->entity->getSubtype()}";
        }

        return $this->eventsDispatcher->trigger('entity:save', $namespace, [
            'entity' => $this->entity,
        ], $success);
    }

    /**
     * Manipulate all compliant entities before saving.
     */
    protected function beforeSave()
    {
        $this->tagNSFW();
        $this->applyLanguage();
    }

    /**
     * Applies language to entry by setting it to the owners language.
     *
     * @return void
     */
    public function applyLanguage(): void
    {
        try {
            if (!$this->entity->language) {
                $owner = $this->entity->getOwnerGuid() ? $this->entitiesBuilder->single($this->entity->getOwnerGuid()) : null;
                if ($owner instanceof User && $owner->language) {
                    $this->entity->language = $owner->language;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "Error applying language to "
                .$this->entity->type ?? "entity"." with guid "
                .$this->entity->guid
            );
        }
    }

    protected function tagNSFW()
    {
        $nsfwReasons = [];

        if (method_exists($this->entity, 'getNSFW')) {
            $nsfwReasons = array_merge($nsfwReasons, $this->entity->getNSFW());
            $nsfwReasons = array_merge($nsfwReasons, $this->entity->getNSFWLock());
        }

        if (
            $this->entity->getOwnerGuid()
            && ($owner = $this->entitiesBuilder->single($this->entity->getOwnerGuid()))
            && $owner instanceof User
        ) {
            
            $nsfwReasons = array_merge($nsfwReasons, $owner->getNSFW());
            $nsfwReasons = array_merge($nsfwReasons, $owner->getNSFWLock());
            // Legacy explicit follow through
            if ($owner->isMature()) {
                $nsfwReasons = array_merge($nsfwReasons, [6]);
                if (MagicAttributes::setterExists($this->entity, 'setMature')) {
                    $this->entity->setMature(true);
                } elseif (method_exists($this->entity, 'setFlag')) {
                    $this->entity->setFlag('mature', true);
                }
            }
        }

        if (method_exists($this->entity, 'getContainerGuid') && $this->entity->getContainerGuid()) {
            $container = $this->entitiesBuilder->single($this->entity->getContainerGuid());
            $nsfwReasons = array_merge($nsfwReasons, $container->getNSFW());
            $nsfwReasons = array_merge($nsfwReasons, $container->getNSFWLock());
        }

        $this->entity->setNSFW($nsfwReasons);
    }
}
