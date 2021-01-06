<?php
namespace Minds\Core\Wire\Paywall;

use Minds\Core\Di\Di;
use Minds\Core\Wire\Thresholds;
use Minds\Entities\User;
use Minds\Entities\Entity;
use Minds\Helpers\MagicAttributes;
use Minds\Core\Wire\SupportTiers;

class Manager
{
    /** @var User */
    protected $user;

    /** @var Thresholds */
    protected $wireThresholds;

    /** @var SupportTiers\Manager */
    protected $supportTiersManager;

    /** @var Delegates\MetricsDelegate */
    protected $metricsDelegate;

    public function __construct(
        $wireThresholds = null,
        $supportTiersManager = null,
        $metricsDelegate = null
    ) {
        $this->wireThresholds = $wireTresholds ?? Di::_()->get('Wire\Thresholds');
        $this->supportTiersManager = $supportTiersManager ?? Di::_()->get('Wire\SupportTiers\Manager');
        $this->metricsDelegate = $metricsDelegate ?? new Delegates\MetricsDelegate();
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
     * @param PaywallEntityInterface $entity
     * @return bool
     */
    public function isPaywalled(PaywallEntityInterface $entity): bool
    {
        if ((MagicAttributes::getterExists($entity, 'isPayWall') || method_exists($entity, 'isPayWall'))
            && $entity->isPayWall()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Validate the entity and patch the wire threshold
     * @param PaywallEntityInterface $entity
     * @param bool $patch
     * @return void
     */
    public function validateEntity(PaywallEntityInterface $entity, $patch = true): void
    {
        $wireThreshold = $entity->getWireThreshold();
        
        // a removed paywall entity is a valid edit.
        if (!$wireThreshold) {
            $entity->setPayWall(false);
            return;
        }

        if (!is_array($wireThreshold)) {
            throw new PaywallInvalidCreationInputException();
        }

        if ($wireThreshold['support_tier'] ?? null) {
            // V2 of Paywall
            $urn = $wireThreshold['support_tier']['urn'] ?? null;
            $expires = $wireThreshold['support_tier']['expires'] ?? 0;

            if (!$urn) {
                throw new PaywallInvalidCreationInputException();
            }

            $supportTier = $this->supportTiersManager->getByUrn($urn);
            if (!$supportTier) {
                throw new PaywallSupportTierNotFoundException();
            }

            // Sanitize the the wireThreshold array
            $entity->setWireThreshold([
                'support_tier' => [
                    'urn' => $urn,
                    'expires' => $expires
                ]
            ]);
        } elseif (($wireThreshold['min'] ?? 0) > 0 && $wireThreshold['type'] ?? null) {
            // Legacy version which will soon be removed
            // Nothing to do here, as the data is already set
        } else {
            throw new PaywallInvalidCreationInputException();
        }

        $entity->setPayWall(true);
    }

    /**
     * @param PaywallEntityInterface $entity
     * @return bool
     */
    public function isAllowed(PaywallEntityInterface $entity): bool
    {
        if (!$this->user) {
            return false;
        }
        return $this->wireThresholds->isAllowed($this->user, $entity);
    }

    /**
     * Unlocks and entity
     * @param PaywallEntityInterface $entity
     */
    public function unlock(PaywallEntityInterface $entity): PaywallEntityInterface
    {
        if ($this->isAllowed($entity) || $this->user->isAdmin()) {
            $entity->setPayWallUnlocked(true);
        } else {
            throw new PaywallUserNotPaid();
        }

        $this->metricsDelegate->onUnlock($entity, $this->user);

        return $entity;
    }
}
