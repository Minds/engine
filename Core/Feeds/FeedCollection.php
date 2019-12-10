<?php
/**
 * FeedCollection.
 *
 * @author edgebal
 */
namespace Minds\Core\Feeds;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Clock;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Entities as ElasticEntities;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Entities\User;

class FeedCollection
{
    /** @var string[] */
    const ALGORITHMS = [
        'top',
        'hot',
        'latest'
    ];

    /** @var array */
    const PERIODS = [
        '12h' => 43200,
        '24h' => 86400,
        '7d' => 604800,
        '30d' => 2592000,
        '1y' => 31536000,
    ];

    /** @var array */
    const PERIOD_FALLBACK = [
        '12h' => '7d',
        '24h' => '7d',
        '7d' => '30d',
        '30d' => '1y'
    ];

    /** @var string[] */
    const ALLOWED_TO_FALLBACK = [
        'top'
    ];

    /** @var User|null */
    protected $actor = null;

    /** @var string */
    protected $filter;

    /** @var string */
    protected $algorithm;

    /** @var string */
    protected $type;

    /** @var string */
    protected $period;

    /** @var int */
    protected $limit = 12;

    /** @var int */
    protected $offset = 0;

    /** @var int */
    protected $cap = 600;

    /** @var bool */
    protected $all = true;

    /** @var string[]|null */
    protected $hashtags = null;

    /** @var bool */
    protected $sync = false;

    /** @var bool */
    protected $periodFallback = false;

    /** @var bool */
    protected $asActivities = false;

    /** @var string */
    protected $query = '';

    /** @var string */
    protected $customType = '';

    /** @var string|null */
    protected $containerGuid;

    /** @var string[]|null */
    protected $nsfw = [];

    /** @var int[]|null */
    protected $accessIds = null;

    /** @var int */
    protected $singleOwnerThreshold = 0;

    /** @var ElasticManager */
    protected $elasticManager;

    /** @var ElasticEntities */
    protected $elasticEntities;

    /** @var UserHashtagsManager */
    protected $userHashtagsManager;

    /** @var Clock */
    protected $clock;

    /**
     * FeedCollection constructor.
     * @param ElasticManager $elasticManager
     * @param ElasticEntities $elasticEntities
     * @param UserHashtagsManager $userHashtagsManager
     * @param Clock $clock
     */
    public function __construct(
        $elasticManager = null,
        $elasticEntities = null,
        $userHashtagsManager = null,
        $clock = null
    )
    {
        $this->elasticManager = $elasticManager ?: Di::_()->get('Feeds\Elastic\Manager');
        $this->elasticEntities = $elasticEntities ?: new ElasticEntities();
        $this->userHashtagsManager = $userHashtagsManager ?: Di::_()->get('Hashtags\User\Manager');
        $this->clock = $clock ?: new Clock();
    }

    /**
     * @param User|null $actor
     * @return FeedCollection
     */
    public function setActor(?User $actor): FeedCollection
    {
        $this->actor = $actor;
        return $this;
    }

    /**
     * @param string $filter
     * @return FeedCollection
     */
    public function setFilter(string $filter): FeedCollection
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @param string $algorithm
     * @return FeedCollection
     */
    public function setAlgorithm(string $algorithm): FeedCollection
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * @param string $type
     * @return FeedCollection
     */
    public function setType(string $type): FeedCollection
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $period
     * @return FeedCollection
     */
    public function setPeriod(string $period): FeedCollection
    {
        $this->period = $period;
        return $this;
    }

    /**
     * @param int $limit
     * @return FeedCollection
     */
    public function setLimit(int $limit): FeedCollection
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int $offset
     * @return FeedCollection
     */
    public function setOffset(int $offset): FeedCollection
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param int $cap
     * @return FeedCollection
     */
    public function setCap(int $cap): FeedCollection
    {
        $this->cap = $cap;
        return $this;
    }

    /**
     * @param bool $all
     * @return FeedCollection
     */
    public function setAll(bool $all): FeedCollection
    {
        $this->all = $all;
        return $this;
    }

    /**
     * @param array|null $hashtags
     * @return FeedCollection
     */
    public function setHashtags(?array $hashtags): FeedCollection
    {
        $this->hashtags = $hashtags;
        return $this;
    }

    /**
     * @param bool $sync
     * @return FeedCollection
     */
    public function setSync(bool $sync): FeedCollection
    {
        $this->sync = $sync;
        return $this;
    }

    /**
     * @param bool $periodFallback
     * @return FeedCollection
     */
    public function setPeriodFallback(bool $periodFallback): FeedCollection
    {
        $this->periodFallback = $periodFallback;
        return $this;
    }

    /**
     * @param bool $asActivities
     * @return FeedCollection
     */
    public function setAsActivities(bool $asActivities): FeedCollection
    {
        $this->asActivities = $asActivities;
        return $this;
    }

    /**
     * @param string $query
     * @return FeedCollection
     */
    public function setQuery(string $query): FeedCollection
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param string $customType
     * @return FeedCollection
     */
    public function setCustomType(string $customType): FeedCollection
    {
        $this->customType = $customType;
        return $this;
    }

    /**
     * @param string|null $containerGuid
     * @return FeedCollection
     */
    public function setContainerGuid(?string $containerGuid): FeedCollection
    {
        $this->containerGuid = $containerGuid;
        return $this;
    }

    /**
     * @param string[]|null $nsfw
     * @return FeedCollection
     */
    public function setNsfw(?array $nsfw): FeedCollection
    {
        $this->nsfw = $nsfw;
        return $this;
    }

    /**
     * @param int[]|null $accessIds
     * @return FeedCollection
     */
    public function setAccessIds(?array $accessIds): FeedCollection
    {
        $this->accessIds = $accessIds;
        return $this;
    }

    /**
     * @param int $singleOwnerThreshold
     * @return FeedCollection
     */
    public function setSingleOwnerThreshold(int $singleOwnerThreshold): FeedCollection
    {
        $this->singleOwnerThreshold = $singleOwnerThreshold;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function fetch()
    {
        if (!$this->filter) {
            throw new Exception('Missing filter');
        }

        if (!$this->algorithm /* TODO: Validate */) {
            throw new Exception('Missing algorithm');
        }

        if (!$this->type /* TODO: Validate */) {
            throw new Exception('Missing type');
        }

        if (!$this->period /* TODO: Validate */) {
            throw new Exception('Missing period');
        }

        // Normalize period

        $period = $this->period;

        switch ($this->algorithm) {
            case 'hot':
                $period = '12h';
                break;

            case 'latest':
                $period = '1y';
                break;
        }

        // Normalize and calculate limit

        $offset = abs(intval($this->offset ?: 0));
        $limit = abs(intval($this->limit ?: 0));

        if ($limit) {
            if ($this->cap && ($offset + $limit) > $this->cap) {
                $limit = $this->cap - $offset;
            }

            if ($limit < 0) {
                $emptyResponse = new Response([]);
                $emptyResponse
                    ->setPagingToken((string) $this->cap)
                    ->setLastPage(true)
                    ->setAttribute('overflow', true);

                return $emptyResponse;
            }
        }

        // Normalize hashtags

        $all = !$this->hashtags && $this->all;
        $hashtags = $this->hashtags;
        $filterHashtags = true;

        // Fetch preferred hashtags

        if (!$all && !$hashtags && $this->actor) {
            $hashtags = $this->userHashtagsManager
                ->setUser($this->actor)
                ->values([
                    'limit' => 50,
                    'trending' => false,
                    'defaults' => false,
                ]);

            $filterHashtags = false;
        }

        // Check container readability

        if ($this->containerGuid) {
            $container = $this->entitiesBuilder->single($this->containerGuid);

            if (!$container || !$this->acl->read($container)) {
                throw new Exception('Forbidden container');
            }
        }

        // Build options

        $opts = [
            'cache_key' => $this->actor ? (string) $this->actor->guid : null,
            'container_guid' => $this->containerGuid,
            'access_id' => $this->accessIds,
            'custom_type' => $this->customType,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'type' => $this->type,
            'algorithm' => $this->algorithm,
            'period' => $period,
            'sync' => $this->sync,
            'query' => $this->query,
            'single_owner_threshold' => $this->singleOwnerThreshold,
            'as_activities' => $this->asActivities,
            'nsfw' => $this->nsfw,
            'hashtags' => $hashtags,
            'filter_hashtags' => $filterHashtags
        ];

        //

        $response = new Response();
        $fallbackAt = null;
        $i = 0;

        while ($response->count() < $limit) {
            $result = $this->elasticManager->getList($opts);

            $response = $response
                ->pushArray($result->toArray());

            if (
                !$this->periodFallback ||
                !in_array($this->algorithm, static::ALLOWED_TO_FALLBACK, true) ||
                !isset(static::PERIOD_FALLBACK[$opts['period']]) ||
                ++$i > 2 // Stop at 2nd fallback (i.e. 12h > 7d > 30d)
            ) {
                break;
            }

            $period = $opts['period'];
            $from = $this->clock->now() - static::PERIODS[$period];
            $opts['from_timestamp'] = $from * 1000;
            $opts['period'] = static::PERIOD_FALLBACK[$period];

            if (!$fallbackAt) {
                $fallbackAt = $from;
            }
        }

        if (!$this->sync) {
            $this->elasticEntities
                ->setActor($this->actor);

            $response = $response
                ->filter([$this->elasticEntities, 'filter']);

            if ($this->asActivities) {
                $response = $response
                    ->map([$this->elasticEntities, 'cast']);
            }
        }

        $response
            ->setPagingToken($this->limit + $this->offset)
            ->setAttribute('fallbackAt', $fallbackAt);

        return $response;
    }
}
