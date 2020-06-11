<?php
namespace Minds\Core\Wire\Paywall;

use Minds\Core\Di\Di;
use Minds\Core\Wire\Thresholds;
use Minds\Entities\User;
use Minds\Helpers\MagicAttributes;

class Manager
{
    /** @var User */
    protected $user;

    /** @var Thresholds */
    protected $wireThresholds;

    public function __construct($wireThresholds = null)
    {
        $this->wireThresholds = $wireTresholds ?? Di::_()->get('Wire\Thresholds');
    }

    /**
     * @param User $user
     * @return Manager
     */
    public function setUser(?User $user): Manager
    {
        $manager = clone $this;
        $manager->user = $user;
        return $manager;
    }

    /**
     * Return if the entity is paywalled
     * @param Entity $entity
     * @return bool
     */
    public function isPaywalled($entity): bool
    {
        if ((MagicAttributes::getterExists($entity, 'isPaywall') || method_exists($entity, 'isPaywall'))
            && $entity->isPaywall()
        ) {
            return true;
        } elseif (method_exists($entity, 'getFlag') && $entity->getFlag('paywall')) {
            return true;
        }
        return false;
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function isAllowed($entity): bool
    {
        if (!$this->user) {
            return false;
        }
        return $this->wireThresholds->isAllowed($this->user, $entity);
    }
}
