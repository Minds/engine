<?php
/**
 * Repository
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Boost\Elastic\RawElasticBoost;
use Minds\Core\Boost\Elastic\Repository as ElasticBoostRepository;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Helpers\Text;
use NotImplementedException;

class Repository
{
    /** @var ElasticBoostRepository $elasticBoostRepository */
    protected $elasticBoostRepository;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /**
     * Repository constructor.
     * @param EntitiesBuilder $entitiesBuilder
     * @param ElasticBoostRepository $elasticBoostRepository
     */
    public function __construct(
        $entitiesBuilder = null,
        $elasticBoostRepository = null
    )
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->elasticBoostRepository = $elasticBoostRepository ?: new ElasticBoostRepository();
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

        $result = $this->elasticBoostRepository->getList([
            'is_campaign' => true,
            'owner_guid' => $opts['owner_guid'],
            'limit' => $opts['limit'],
            'offset' => $opts['offset'],
            'guid' => $opts['guid'],
            'sort' => 'desc',
        ]);

        return $result->map(function (RawElasticBoost $rawElasticBoost) {
            $campaign = new Campaign();

            // Entities

            $entityUrns = array_map(function($entityUrn) {
                if (is_numeric($entityUrn)) {
                    $entityUrn = "urn:entity:{$entityUrn}";
                }

                return $entityUrn;
            }, Text::buildArray($rawElasticBoost->getEntityUrns()));

            // Hashtags

            $tags = Text::buildArray($rawElasticBoost->getTags());

            // Delivery Status

            $deliveryStatus = 'created';
            // TODO: Calculate based on timestamps

            // Campaign

            $campaign
                ->setOwnerGuid($rawElasticBoost->getOwnerGuid())
                ->setName($rawElasticBoost->getCampaignName())
                ->setType($rawElasticBoost->getType())
                ->setEntityUrns($entityUrns)
                ->setHashtags($tags)
                ->setStart($rawElasticBoost->getCampaignStart())
                ->setEnd($rawElasticBoost->getCampaignEnd())
                ->setBudget($rawElasticBoost->getBid())
                ->setDeliveryStatus($deliveryStatus)
                ->setUrn("urn:campaign:{$rawElasticBoost->getGuid()}")
                ->setImpressions($rawElasticBoost->getImpressions())
                ->setImpressionsMet($rawElasticBoost->getImpressionsMet());

            return $campaign;
        });
    }

    /**
     * @param Campaign $campaign
     * @return bool
     * @throws Exception
     */
    public function add(Campaign $campaign)
    {
        // TODO: Implement token method

        $urn = new Urn($campaign->getUrn());
        $guid = $urn->getNss();

        // Raw Boost

        $rawElasticBoost = new RawElasticBoost();

        $rawElasticBoost
            ->setOwnerGuid($campaign->getOwnerGuid())
            ->setCampaignName($campaign->getName())
            ->setType($campaign->getType())
            ->setEntityUrns($campaign->getEntityUrns())
            ->setTags($campaign->getHashtags())
            ->setCampaign(true)
            ->setCampaignStart($campaign->getStart())
            ->setCampaignEnd($campaign->getEnd())
            ->setBid($campaign->getBudget())
            ->setBidType('tokens')
            ->setGuid($guid)
            ->setImpressions($campaign->getImpressions())
            ->setRating(2)
            ->setPriority(false);

        $cqlSave = true; // TODO: Implement Cassandra Repo
        $esSave = $this->elasticBoostRepository->add($rawElasticBoost);

        return $cqlSave && $esSave;
    }

    /**
     * @param Campaign $campaign
     * @return bool
     * @throws Exception
     */
    public function update(Campaign $campaign)
    {
        return $this->add($campaign);
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
