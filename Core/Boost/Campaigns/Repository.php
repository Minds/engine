<?php
/**
 * Repository
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Boost\Raw\RawBoost;
use Minds\Core\Boost\Raw\ElasticRepository as RawElasticRepository;
use Minds\Core\Boost\Raw\Repository as RawRepository;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Helpers\Text;
use NotImplementedException;

class Repository
{
    /** @var RawElasticRepository $rawElasticRepository */
    protected $rawElasticRepository;

    /** @var RawRepository $rawRepository */
    protected $rawRepository;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /**
     * Repository constructor.
     * @param EntitiesBuilder $entitiesBuilder
     * @param RawRepository $rawRepository
     * @param RawElasticRepository $rawElasticRepository
     */
    public function __construct(
        $entitiesBuilder = null,
        $rawRepository = null,
        $rawElasticRepository = null
    )
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->rawRepository = $rawRepository ?: new RawRepository();
        $this->rawElasticRepository = $rawElasticRepository ?: new RawElasticRepository();
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

        $result = $this->rawElasticRepository->getList([
            'is_campaign' => true,
            'owner_guid' => $opts['owner_guid'],
            'limit' => $opts['limit'],
            'offset' => $opts['offset'],
            'guid' => $opts['guid'],
            'sort' => 'desc',
        ]);

        return $result->map(function (RawBoost $rawBoost) {
            $campaign = new Campaign();

            // Entities

            $entityUrns = array_map(function($entityUrn) {
                if (is_numeric($entityUrn)) {
                    $entityUrn = "urn:entity:{$entityUrn}";
                }

                return $entityUrn;
            }, Text::buildArray($rawBoost->getEntityUrns()));

            // Hashtags

            $tags = Text::buildArray($rawBoost->getTags());

            // Campaign

            $campaign
                ->setOwnerGuid($rawBoost->getOwnerGuid())
                ->setName($rawBoost->getCampaignName())
                ->setType($rawBoost->getType())
                ->setEntityUrns($entityUrns)
                ->setHashtags($tags)
                ->setStart($rawBoost->getCampaignStart())
                ->setEnd($rawBoost->getCampaignEnd())
                ->setBudget($rawBoost->getBid())
                ->setUrn("urn:campaign:{$rawBoost->getGuid()}")
                ->setImpressions($rawBoost->getImpressions())
                ->setImpressionsMet($rawBoost->getImpressionsMet())
                ->setCreatedTimestamp($rawBoost->getCreatedTimestamp())
                ->setReviewedTimestamp($rawBoost->getReviewedTimestamp())
                ->setRejectedTimestamp($rawBoost->getRejectedTimestamp())
                ->setRevokedTimestamp($rawBoost->getRevokedTimestamp())
                ->setCompletedTimestamp($rawBoost->getCompletedTimestamp());

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

        $rawBoost = new RawBoost();

        $rawBoost
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
            ->setRating(1)
            ->setPriority(true)
            ->setCreatedTimestamp($campaign->getCreatedTimestamp())
            ->setReviewedTimestamp($campaign->getReviewedTimestamp())
            ->setRejectedTimestamp($campaign->getRejectedTimestamp())
            ->setRevokedTimestamp($campaign->getRevokedTimestamp())
            ->setCompletedTimestamp($campaign->getCompletedTimestamp());

        $cqlSave = $this->rawRepository->add($rawBoost);
        $esSave = $this->rawElasticRepository->add($rawBoost);

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
