<?php
/**
 * BoostCampaignResolverDelegate
 * @author edgebal
 */

namespace Minds\Core\Entities\Delegates;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\Manager;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;

class BoostCampaignResolverDelegate implements ResolverDelegate
{
    /** @var Resolver */
    protected $resolver;

    /**
     * BoostCampaignResolverDelegate constructor.
     */
    public function __construct()
    {
        // NOTE: Campaigns\Manager is injected dynamically because of circular dependency issues
    }

    /**
     * @param Resolver $resolver
     * @return BoostCampaignResolverDelegate
     */
    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * @param Urn $urn
     * @return boolean
     */
    public function shouldResolve(Urn $urn)
    {
        return $urn->getNid() === 'campaign';
    }

    /**
     * @param Urn[] $urns
     * @param array $opts
     * @return mixed
     * @throws Exception
     */
    public function resolve(array $urns, array $opts = [])
    {
        /** @var Manager $manager */
        $manager = Di::_()->get('Boost\Campaigns\Manager');

        $entities = [];

        foreach ($urns as $urn) {
            /** @var Campaign $campaign */
            $campaign = $manager->getCampaignByUrn($urn);

            $entities[] = $campaign;
        }

        return $entities;
    }

    /**
     * @param string $urn
     * @param Campaign $campaign
     * @return mixed
     */
    public function map($urn, $campaign)
    {
        if (!$campaign || !$campaign->getEntityUrns()) {
            return null;
        }

        $entity = $this->resolver->single(new Urn($campaign->getEntityUrns()[0]));

        if ($entity) {
            $entity->boosted = true;
            $entity->boosted_guid = $campaign->getUrn();
            $entity->boosted_onchain = true;
            $entity->urn = $campaign->getUrn();
        }

        return $entity;
    }

    /**
     * @param Campaign $entity
     * @return string|null
     */
    public function asUrn($entity)
    {
        if (!$entity) {
            return null;
        }

        return $entity->getUrn();
    }
}
