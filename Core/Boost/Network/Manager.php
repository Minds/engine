<?php

namespace Minds\Core\Boost\Network;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\Checksum;
use Minds\Core\Boost\Delegates\ValidateCampaignDatesDelegate;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\GuidBuilder;
use Minds\Core\Events\Dispatcher;
use Minds\Entities\Entity;
use Minds\Entities\User;

class Manager
{
    /** @var CassandraRepository $cassandraRepository */
    private $cassandraRepository;

    /** @var ElasticRepository $elasticRepository */
    private $elasticRepository;

    /** @var EntitiesBuilder $entitiesBuilder */
    private $entitiesBuilder;

    /** @var GuidBuilder $guidBuilder */
    private $guidBuilder;

    /** @var Config $config */
    private $config;

    /** @var User $actor */
    private $actor;

    const OPT_STATEQUERY_ACTIVE = 'active';
    const OPT_STATEQUERY_REVIEW = 'review';
    const OPT_STATEQUERY_APPROVED = 'approved';
    const OPT_PAUSEQUERY_ANY = 'any';

    const VALID_OPT_STATEQUERY = [
        self::OPT_STATEQUERY_ACTIVE,
        self::OPT_STATEQUERY_REVIEW,
        self::OPT_STATEQUERY_APPROVED
    ];

    public function __construct(
        $cassandraRepository = null,
        $elasticRepository = null,
        $entitiesBuilder = null,
        $guidBuilder = null,
        $config = null
    ) {
        $this->cassandraRepository = $cassandraRepository ?: new CassandraRepository;
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
    public function getList($opts = []): Response
    {
        $opts = array_merge([
            'useElastic' => false,
            'state' => null,
        ], $opts);

        if ($this->optStateIsUsedAndValid($opts)) {
            $opts['useElastic'] = true;
        }

        if ($opts['useElastic']) {
            $response = $this->elasticRepository->getList($opts);

            // TODO: Check if it is *really* necessary to re fetch the boosts from cassandra ??
            if ($this->optStateIsUsedAndValid($opts) && $opts['state'] !== self::OPT_STATEQUERY_APPROVED) {
                $opts['guids'] = array_map(function ($boost) {
                    return $boost->getGuid();
                }, $response->toArray());

                if (empty($opts['guids'])) {
                    return $response;
                }

                $loadNext = $response->getPagingToken();
                $response = $this->cassandraRepository->getList($opts);
                $response->setPagingToken($loadNext);
            }
        } else {
            $response = $this->cassandraRepository->getList($opts);
        }

        /**
         * This shouldn't work but it does.
         * Iterator must be returning a boost reference
         */
        foreach ($response as $i => $boost) {
            $boost = $this->hydrate($boost);

            if (!$boost->getEntity() || !$boost->getOwner()) {
                $boost->setEntity(new Entity());
            }
        }

        return $response;
    }

    protected function optStateIsUsedAndValid(array $opts): bool
    {
        return $this->optStateIsUsed($opts) && $this->optStateIsValid($opts);
    }

    protected function optStateIsUsed(array $opts): bool
    {
        return !is_null($opts['state']);
    }

    protected function optStateIsValid(array $opts): bool
    {
        return in_array($opts['state'], self::VALID_OPT_STATEQUERY, true);
    }

    /**
     * Get a single boost
     * @param string $urn
     * @param array $opts
     * @return Boost
     */
    public function get($urn, $opts = [])
    {
        $opts = array_merge([
            'hydrate' => false,
        ], $opts);

        $boost = $this->cassandraRepository->get($urn);

        if ($boost && $opts['hydrate']) {
            $boost = $this->hydrate($boost);
        }

        return $boost;
    }

    /**
     * Add a boost
     * @param Boost|Campaign $boost
     * @return bool
     * @throws \Exception
     */
    public function add($boost)
    {
        if (!$boost->getGuid()) {
            $boost->setGuid($this->guidBuilder->build());
        }
        $this->cassandraRepository->add($boost);
        $this->elasticRepository->add($boost);
        return true;
    }

    /**
     * Update a boost
     * @param Boost|Campaign $boost
     * @param array $fields
     */
    public function update($boost, $fields = [])
    {
        $this->cassandraRepository->update($boost, $fields);
        $this->resync($boost, $fields);
    }

    public function resync($boost, $fields = [])
    {
        $this->elasticRepository->update($boost, $fields);
    }

    /**
     * Checks if a boost already exists for a given entity
     * @param Boost $boost
     * @return bool
     */
    public function checkExisting(Boost $boost): bool
    {
        $existingBoost = $this->getList([
            'state' => self::OPT_STATEQUERY_REVIEW,
            'type' => $boost->getType(),
            'entity_guid' => $boost->getEntityGuid(),
            'limit' => 1
        ]);

        return $existingBoost->count() > 0;
    }

    /**
     * Hydrate the boost object with entity and owner
     * @param Boost $boost
     * @return Boost
     */
    public function hydrate(Boost $boost): Boost
    {
        $boost->setEntity($this->entitiesBuilder->single($boost->getEntityGuid()));
        $boost->setOwner($this->entitiesBuilder->single($boost->getOwnerGuid()));
        return $boost;
    }

    public function expire(Boost $boost): void
    {
        if ($boost->getState() === Boost::STATE_COMPLETED) {
            $this->resync($boost);
        }

        $boost->setCompletedTimestamp(round(microtime(true) * 1000));
        $this->update($boost);

        Dispatcher::trigger('boost:completed', 'boost', ['boost' => $boost]);

        Dispatcher::trigger('notification', 'boost', [
            'to' => [$boost->getOwnerGuid()],
            'from' => 100000000000000519,
            'entity' => $boost->getEntity(),
            'notification_view' => 'boost_completed',
            'params' => [
                'impressions' => $boost->getImpressions(),
                'title' => $boost->getEntity()->title ?: $boost->getEntity()->message
            ],
            'impressions' => $boost->getImpressions()
        ]);
    }

    /**
     * True if the boost is invalid due to the offchain boost limit being reached
     *
     * @param Boost $boost
     * @return boolean true if the boost limit has been reached.
     */
    public function isBoostLimitExceededBy(Boost $boost): bool
    {
        if ($boost->isOnChain()) {
            return false;
        }

        $offchainBoosts = $this->getOffchainBoosts($boost->getType(), $boost->getOwnerGuid());

        $offchainTransactionsToday = array_filter($offchainBoosts->toArray(), function ($boost) {
            return $boost->getCreatedTimestamp() > (time() - (60 * 60 * 24)) * 1000;
        });

        $boostImpressionsRequestedToday = array_reduce($offchainTransactionsToday, function ($impressions, $boost) {
            $impressions += $boost->getImpressions();
            return $impressions;
        }, 0);

        $maxDaily = $this->config->get('max_daily_boost_views');
        return $boostImpressionsRequestedToday + $boost->getImpressions() > $maxDaily;
    }


    /**
     * Gets the users last offchain boosts, from the most recent boost backwards in time.
     *
     * @param string $type the type of the boost
     * @param integer $limit default to 10.
     * @return Response
     */
    public function getOffchainBoosts(string $type, int $ownerGuid, $limit = 10): Response
    {
        $offchainBoosts = $this->getList([
            'state' => self::OPT_STATEQUERY_ACTIVE,
            'type' => $type,
            'limit' => $limit,
            'order' => 'desc',
            'offchain' => true,
            'owner_guid' => $ownerGuid,
        ]);

        return $offchainBoosts;
    }

    public function getCampaigns(array $opts)
    {
        $opts = array_merge([
            'owner_guid' => $this->actor->getGUID(),
            'boost_type' => Boost::BOOST_TYPE_CAMPAIGN
        ], $opts);
        $response = $this->elasticRepository->getList($opts);

        /** @var Metrics $metrics */
        $metrics = Di::_()->get('Boost\Network\Metrics');

        /** @var Campaign $campaign */
        foreach ($response as $campaign) {
            $todayImpressions = $metrics->getDailyViews($campaign);
            $totalImpressions = $metrics->getTotalViews($campaign);
            $campaign->setTodayImpressions($todayImpressions);
            $campaign->setImpressionsMet($totalImpressions);
        }

        return $response;
    }

    public function setActor(User $user): self
    {
        $this->actor = $user;
        return $this;
    }

    public function createCampaign(Campaign $campaign): Campaign
    {
        if (!$campaign->getGuid()) {
            $campaign->setGuid($this->guidBuilder->build());
        }
        $campaign->setOwnerGuid($this->actor->getGUID());

        if (!$campaign->getOwnerGuid()) {
            throw new CampaignException('Campaign should have an owner');
        }

        if (!$campaign->getName()) {
            throw new CampaignException('Campaign should have a name');
        }

        $validTypes = ['newsfeed', 'content', 'banner', 'video'];

        if (!in_array($campaign->getType(), $validTypes, true)) {
            throw new CampaignException('Invalid campaign type');
        }

        /** TODO: Checksum Verification
        $checksum = (new Checksum())->setGuid($campaign->getGuid())->setEntity($campaign->getEntityGuid())->generate();
        if (!$campaign->getChecksum() || ($campaign->getChecksum() !== $checksum)) {
            throw new CampaignException('Invalid checksum value');
        }*/

        $campaign = (new ValidateCampaignDatesDelegate())->onCreate($campaign);

        $this->add($campaign);

        return $campaign;
    }

    public function updateCampaign(Campaign $campaign): Campaign
    {
        // TODO: Implement this...
        return $campaign;
    }

    public function cancelCampaign(Campaign $campaign): Campaign
    {
        // TODO: Implement this...
        return $campaign;
    }
}
