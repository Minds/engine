<?php
/**
 * BoostGuidResolverDelegate.
 *
 * @author emi
 */

namespace Minds\Core\Entities\Delegates;

use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Boost\V3\Manager as BoostManagerV3;
use Minds\Entities\Boost\BoostEntityInterface;
use Minds\Core\Experiments\Manager as ExperimentsManager;

class BoostGuidResolverDelegate implements ResolverDelegate
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * BoostGuidResolverDelegate constructor.
     * @param Manager $manager
     * @param BoostManagerV3 $managerV3
     * @param ExperimentsManager $experimentsManager
     */
    public function __construct(
        $manager = null,
        private ?BoostManagerV3 $managerV3 = null,
        private ?ExperimentsManager $experimentsManager = null
    ) {
        $this->manager = $manager ?: Di::_()->get('Boost\Network\Manager');
    }

    /**
     * @param Urn $urn
     * @return boolean
     */
    public function shouldResolve(Urn $urn): bool
    {
        return $urn->getNid() === 'boost';
    }

    /**
     * @param array $urns
     * @param array $opts
     * @return mixed
     */
    public function resolve(array $urns, array $opts = []): ?array
    {
        $entities = [];

        foreach ($urns as $urn) {
            /** @var BoostEntityInterface $boost */
            $boost = $this->isDynamicBoostExperimentActive() ?
                $this->getBoostManagerV3()->getBoostByGuid(end(explode(':', $urn))) :
                $this->manager->get($urn, [ 'hydrate' => true ]);
            $entities[] = $boost;
        }

        return $entities;
    }

    /**
     * @param BoostEntityInterface $entity
     * @return mixed
     */
    public function map($urn, $entity)
    {
        $boostedEntity = $entity->getEntity();

        if ($boostedEntity) {
            $boostedEntity->boosted = true;
            $boostedEntity->boosted_guid = $entity->getGuid();
            $boostedEntity->boosted_onchain = $entity->isOnChain();
            $boostedEntity->urn = $urn;
        }

        return $boostedEntity;
    }

    /**
     * @param BoostEntityInterface $entity
     * @return string|null
     */
    public function asUrn($entity): ?string
    {
        if (!$entity) {
            return null;
        }

        return $this->isDynamicBoostExperimentActive() ?
            "urn:boost:{$entity->getGuid()}" :
            "urn:boost:{$entity->getType()}:{$entity->getGuid()}";
    }

    /**
     * Whether dynamic boost experiment is active.
     * @return boolean true if experiment is active.
     */
    private function isDynamicBoostExperimentActive(): bool
    {
        return $this->getExperimentManager()->isOn('epic-293-dynamic-boost');
    }

    /**
     * Get BoostManagerV3 as it cannot be passed via constructor.
     * @return BoostManagerV3
     */
    private function getBoostManagerV3(): BoostManagerV3
    {
        if (!$this->managerV3) {
            $this->managerV3 = Di::_()->get(BoostManagerV3::class);
        }
        return $this->managerV3;
    }

    /**
     * Get ExperimentsManager as it cannot be passed via constructor.
     * @return ExperimentsManager
     */
    private function getExperimentManager(): ExperimentsManager
    {
        if (!$this->experimentsManager) {
            $this->experimentsManager = Di::_()->get('Experiments\Manager');
        }
        return $this->experimentsManager;
    }
}
