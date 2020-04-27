<?php
/**
 * Network boost manager
 */

namespace Minds\Core\Boost\Network;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\Exceptions\EntityAlreadyBoostedException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\GuidBuilder;

class Manager
{
    /** @var Repository $repository */
    private $repository;

    /** @var ElasticRepository $repository */
    private $elasticRepository;

    /** @var EntitiesBuilder $entitiesBuilder */
    private $entitiesBuilder;

    /** @var GuidBuilder $guidBuilder */
    private $guidBuilder;

    /** @var Config $config */
    private $config;

    public function __construct(
        $repository = null,
        $elasticRepository = null,
        $entitiesBuilder = null,
        $guidBuilder = null,
        $config = null
    ) {
        $this->repository = $repository ?: new Repository;
        $this->elasticRepository = $elasticRepository ?: new ElasticRepository;
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->guidBuilder = $guidBuilder ?: new GuidBuilder;
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * Return a list of boost
     * @param array $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'hydrate' => true,
            'useElastic' => false,
            'state' => null,
        ], $opts);

        if ($opts['state'] == 'review' || $opts['state'] == 'active') {
            $opts['useElastic'] = true;
        }

        if ($opts['useElastic']) {
            $response = $this->elasticRepository->getList($opts);

            if ($opts['state'] === 'review' || $opts['state'] === 'active') {
                $opts['guids'] = array_map(function ($boost) {
                    return $boost->getGuid();
                }, $response->toArray());

                if (empty($opts['guids'])) {
                    return $response;
                }

                $loadNext = $response->getPagingToken();
                $response = $this->repository->getList($opts);
                $response->setPagingToken($loadNext);
            }
        } else {
            $response = $this->repository->getList($opts);
        }

        if (!$opts['hydrate']) {
            return $response;
        }

        foreach ($response as $i => $boost) {
            $boost->setEntity($this->entitiesBuilder->single($boost->getEntityGuid()));
            $boost->setOwner($this->entitiesBuilder->single($boost->getOwnerGuid()));

            if (!$boost->getEntity() || !$boost->getOwner()) {
                $boost->setEntity(new \Minds\Entities\Entity());
                //    unset($response[$i]);
            }
        }

        return $response;
    }

    /**
     * Get a single boost
     * @param string $urn
     * @return Boost
     */
    public function get($urn, $opts = [])
    {
        $opts = array_merge([
            'hydrate' => false,
        ], $opts);

        $boost = $this->repository->get($urn);

        if ($boost && $opts['hydrate']) {
            $boost->setEntity($this->entitiesBuilder->single($boost->getEntityGuid()));
            $boost->setOwner($this->entitiesBuilder->single($boost->getOwnerGuid()));
        }

        return $boost;
    }

    /**
     * Add a boost
     * @param Boost $boost
     * @return bool
     * @throws EntityAlreadyBoostedException
     */
    public function add($boost)
    {
        if (!$boost->getGuid()) {
            $boost->setGuid($this->guidBuilder->build());
        }
        $this->repository->add($boost);
        $this->elasticRepository->add($boost);
        return true;
    }

    public function update($boost, $fields = [])
    {
        $this->repository->update($boost, $fields);
        $this->resync($boost, $fields);
    }

    public function resync($boost, $fields = [])
    {
        $this->elasticRepository->update($boost, $fields);
    }

    /**
     * Checks if a boost already exists for a given entity
     * @param $boost
     * @return bool
     */
    public function checkExisting($boost)
    {
        $existingBoost = $this->getList([
            'useElastic' => true,
            'state' => 'review',
            'type' => $boost->getType(),
            'entity_guid' => $boost->getEntityGuid(),
            'limit' => 1
        ]);

        return $existingBoost->count() > 0;
    }

    /**
     * True if the boost is invalid due to the offchain boost limit being reached
     *
     * @param Boost $type the Boost object.
     * @return boolean true if the boost limit has been reached.
     */
    public function isBoostLimitExceededBy($boost)
    {
        //onchain boosts allowed
        if ($boost->isOnChain()) {
            return false;
        }

        // admins can boost
        if ($boost->getOwner() && $boost->getOwner()->isAdmin()) {
            return false;
        }

        if ($boost->getOwner() && $boost->getOwner()->isPro()) {
            return false;
        }

        //get offchain boosts
        $offchain = $this->getOffchainBoosts($boost);
        
        //filter to get todays offchain transactions
        $offlineToday = array_filter($offchain->toArray(), function ($result) {
            return $result->getCreatedTimestamp() > (time() - (60 * 60 * 24)) * 1000;
        });
        
        //reduce the impressions to count the days boosts.
        $acc = array_reduce($offlineToday, function ($carry, $_boost) {
            $carry += $_boost->getImpressions();
            return $carry;
        }, 0);

        $maxDaily = $this->config->get('max_daily_boost_views');
        return $acc + $boost->getImpressions() > $maxDaily; //still allow 10k
    }
    

    /**
     * Gets the users last offchain boosts, from the most recent boost backwards in time.
     *
     * @param string $type the type of the boost
     * @param integer $limit default to 10.
     * @return $existingBoosts
     */
    public function getOffchainBoosts($boost, $limit = 10)
    {
        $existingBoosts = $this->getList([
            'useElastic' => true,
            'state' => 'active',
            'type' => $boost->getType(),
            'limit' => $limit,
            'order' => 'desc',
            'offchain' => true,
            'owner_guid' => $boost->getOwnerGuid(),
        ]);
        return $existingBoosts;
    }
}
