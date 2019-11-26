<?php

namespace Minds\Core\Boost\Campaigns;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Counters\Manager as Counters;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Traits\DiAlias;

class Metrics
{
    use DiAlias;

    /** @var Counters */
    protected $counters;

    /** @var Resolver */
    protected $resolver;

    /** @var Campaign */
    protected $campaign;

    /**
     * Metrics constructor.
     * @param Counters $counters
     * @param Resolver $resolver
     * @throws Exception
     */
    public function __construct(
        $counters = null,
        $resolver = null
    ) {
        $this->counters = $counters ?: Di::_()->get('Counters');
        $this->resolver = $resolver ?: new Resolver();
    }

    /**
     * @param Campaign $campaign
     * @return Metrics
     * @throws Exception
     */
    public function setCampaign(Campaign $campaign): self
    {
        $this->campaign = $campaign;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function increment(): void
    {
        $this->incrementGlobalBoostCounter();
        $this->incrementCampaignBoostCounter();
        $this->incrementEntityCounters();
    }

    protected function incrementGlobalBoostCounter(): void
    {
        $this->counters
            ->setEntityGuid(0)
            ->setMetric('boost_impressions')
            ->increment();
    }

    protected function incrementCampaignBoostCounter(): void
    {
        $this->counters
            ->setEntityGuid($this->campaign->getGuid())
            ->setMetric('boost_impressions')
            ->increment();
    }

    protected function incrementEntityCounters(): void
    {
        foreach ($this->campaign->getEntityUrns() as $entityUrn) {
            // NOTE: Campaigns have a _single_ entity, for now. Refactor this when we support multiple
            // Ideally, we should use a composite URN, like: urn:campaign-entity:100000321:(urn:activity:100000500)
            $entity = $this->resolver->single(new Urn($entityUrn));

            if ($entity) {
                $this->counters
                    ->setEntityGuid($entity->guid)
                    ->setMetric('impression')
                    ->increment();

                $this->counters
                    ->setEntityGuid($entity->owner_guid)
                    ->setMetric('impression')
                    ->increment();
            }
        }
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getImpressionsMet(): int
    {
        return $this->counters
            ->setEntityGuid($this->campaign->getGuid())
            ->setMetric('boost_impressions')
            ->get(false);
    }
}
