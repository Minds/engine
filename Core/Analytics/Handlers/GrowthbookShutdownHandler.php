<?php
namespace Minds\Core\Analytics\Handlers;

use Minds\Core\Di\Di;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Analytics\Snowplow\Manager as SnowplowManager;
use Minds\Interfaces\ShutdownHandlerInterface;
use Minds\Core\Analytics\Snowplow\Events\SnowplowGrowthbookEvent;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Core\Data\cache\PsrWrapper;

class GrowthbookShutdownHandler implements ShutdownHandlerInterface
{
    // Expire time for cache key.
    const CACHE_TTL = 86400;

    public function __construct(
        private ?ExperimentsManager $experimentsManager = null,
        private ?SnowplowManager $snowplowManager = null,
        private ?PsrWrapper $cache = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->experimentsManager = $experimentsManager ?? Di::_()->get('Experiments\Manager');
        $this->snowplowManager = $snowplowManager ?? Di::_()->get('Analytics\Snowplow\Manager');
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Registers shutdown function to fire off growthbook analytics event.
     * @return void
     */
    public function register(): void
    {
        register_shutdown_function(function () {
            $impressions = $this->experimentsManager->getViewedExperiments();

            foreach ($impressions as $impression) {
                $experimentId = $impression->experiment->key;
                $variationId = $impression->result->variationId;

                $cacheKey = $this->getCacheKey($experimentId);
                
                if ($this->cache->get($cacheKey) !== false) {
                    continue; // Skip as we've seen in last 24 hours.
                }

                $spGrowthbookEvent = (new SnowplowGrowthbookEvent())
                    ->setExperimentId($experimentId)
                    ->setVariationId($variationId);

                $user = $this->experimentsManager->getUser();
                $this->snowplowManager->setSubject($user)->emit($spGrowthbookEvent);
            
                $this->cache->set($cacheKey, $variationId, self::CACHE_TTL);
            }
        });
    }

    /**
     * Gets key for cache such that it is unique to a user and the experiment being ran.
     * @param string $experimentId - id of the experiment.
     * @param User|null $user - user we are running experiment for.
     * @return string - cache key.
     */
    private function getCacheKey(string $experimentId): string
    {
        $userId = $this->experimentsManager->getUserId();
        return 'growthbook-experiment-view::'.$userId.'::'.$experimentId;
    }
}
