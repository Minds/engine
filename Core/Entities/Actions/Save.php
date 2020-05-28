<?php

/**
 * Minds Entities Save action.
 *
 * @author emi
 */

namespace Minds\Core\Entities\Actions;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Helpers\MagicAttributes;
use Minds\Core\Log\Logger;

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

    /**
     * Save constructor.
     *
     * @param null $eventsDispatcher
     */
    public function __construct(
        $eventsDispatcher = null,
        $logger = null
    ) {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->logger = $logger ?: Di::_()->get('Logger');
    }

    /**
     * Sets the entity.
     *
     * @param mixed $entity
     *
     * @return Save
     */
    public function setEntity($entity): Save
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Gets the set entity.
     *
     * @return Entity
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }
    /**
     * Saves the entity.
     *
     * @param mixed ...$args
     *
     * @return bool
     *
     * @throws \Minds\Exceptions\StopEventException
     */
    public function save(...$args)
    {
        if (!$this->entity) {
            return false;
        }

        $this->beforeSave();

        if (method_exists($this->entity, 'save')) {
            return $this->entity->save(...$args);
        }

        $namespace = $this->entity->type;

        if ($this->entity->subtype) {
            $namespace .= ":{$this->entity->subtype}";
        }

        return $this->eventsDispatcher->trigger('entity:save', $namespace, [
            'entity' => $this->entity,
        ], false);
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
            if (!$this->entity->language &&
                method_exists($this->entity, 'getOwnerEntity')
            ) {
                $owner = $this->entity->getOwnerEntity();
                if ($owner && $owner->language) {
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

        if (method_exists($this->entity, 'getOwnerEntity') && $this->entity->getOwnerEntity()) {
            $nsfwReasons = array_merge($nsfwReasons, $this->entity->getOwnerEntity()->getNSFW());
            $nsfwReasons = array_merge($nsfwReasons, $this->entity->getOwnerEntity()->getNSFWLock());
            // Legacy explicit follow through
            if ($this->entity->getOwnerEntity()->isMature()) {
                $nsfwReasons = array_merge($nsfwReasons, [ 6 ]);
                if (MagicAttributes::setterExists($this->entity, 'setMature')) {
                    $this->entity->setMature(true);
                } elseif (method_exists($this->entity, 'setFlag')) {
                    $this->entity->setFlag('mature', true);
                }
            }
        }

        if (method_exists($this->entity, 'getContainerEntity') && $this->entity->getContainerEntity()) {
            $nsfwReasons = array_merge($nsfwReasons, $this->entity->getContainerEntity()->getNSFW());
            $nsfwReasons = array_merge($nsfwReasons, $this->entity->getContainerEntity()->getNSFWLock());
        }

        $this->entity->setNSFW($nsfwReasons);
    }
}
