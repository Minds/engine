<?php
/**
 * Repository
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns;

use Minds\Common\Repository\Response;
use Minds\Core\Blogs\Blog;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Boost\Network\ElasticRepository;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use NotImplementedException;

class Repository
{
    /** @var ElasticRepository */
    protected $esRepository;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /**
     * Repository constructor.
     * @param ElasticRepository $esRepository
     * @param EntitiesBuilder $entitiesBuilder
     */
    public function __construct(
        $esRepository = null,
        $entitiesBuilder = null
    )
    {
        $this->esRepository = $esRepository ?: new ElasticRepository();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = [])
    {
        $opts = array_merge([
            'owner_guid' => null,
            'offset' => '',
            'limit' => 12,
            'guid' => null,
        ], $opts);

        $result = $this->esRepository->getList([
            'is_campaign' => true,
            'owner_guid' => $opts['owner_guid'],
            'limit' => $opts['limit'],
            'offset' => $opts['offset'],
            'guid' => $opts['guid'],
            'sort' => 'desc',
        ]);

        // TODO: Add try/catch

        return $result->map(function (Boost $boost) {
            // Hydrate boost

            $boost->setEntity($this->entitiesBuilder->single($boost->getEntityGuid()));

            // Setup campaign entity

            $campaign = new Campaign();

            $campaign
                ->setName($boost->getGuid())
                ->setType($boost->getType())
                ->setEntityUrns(["urn:entity:{$boost->getEntityGuid()}"])
                ->setHashtags([])
                ->setStart($boost->getCreatedTimestamp())
                ->setEnd(0)
                ->setBudget($boost->getBid())
                ->setUrn("urn:campaign:{$boost->getGuid()}")
                ->setDeliveryStatus($boost->getState())
                ->setImpressions($boost->getImpressions())
                ->setCpm(0)
                ->setBoost($boost);

            return $campaign;
        });
    }

    /**
     * @param Campaign $campaign
     * @return bool
     */
    public function add(Campaign $campaign)
    {
        // TODO: Store to Cassandra + ElasticSearch
        return true;
    }

    /**
     * @param Campaign $campaign
     * @return bool
     */
    public function update(Campaign $campaign)
    {
        // TODO: Store to Cassandra + ElasticSearch
        return true;
    }

    /**
     * @param Campaign $campaign
     * @throws NotImplementedException
     */
    public function delete(Campaign $campaign)
    {
        throw new NotImplementedException();
    }
}
