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
use Minds\Core\Boost\V3\Models\Boost as BoostV3;
use Minds\Entities\Boost\BoostEntityInterface;

class BoostGuidResolverDelegate implements ResolverDelegate
{
    /**
     * BoostGuidResolverDelegate constructor.
     * @param BoostManagerV3 $managerV3
     */
    public function __construct(
        private ?BoostManagerV3 $managerV3 = null,
    ) {
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
            $boost = $this->getBoostManagerV3()->getBoostByGuid(end(explode(':', $urn)));

            if ($boost) {
                $entities[] = $boost;
            }
        }

        return $entities;
    }

    /**
     * @param BoostEntityInterface $entity
     * @return mixed
     */
    public function map($urn, $entity)
    {
        if (!$this->isLegacyUrn($urn)) {
            return $entity; // do not map non-legacy URNs.
        }

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

        if ($entity instanceof BoostV3) {
            return $entity->getUrn();
        }

        return "urn:boost:{$entity->getType()}:{$entity->getGuid()}";
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
     * Check if the URN is a legacy boost URN.
     * @param string $urn - urn to check.
     * @return boolean true if URN is a legacy URN.
     */
    private function isLegacyUrn(string $urn): bool
    {
        return substr_count($urn, ':') === 3;
    }
}
