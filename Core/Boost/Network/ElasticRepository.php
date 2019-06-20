<?php
/**
 * Elasticsearch repository for Boost
 */
namespace Minds\Core\Boost\Network;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Boost\Elastic\RawElasticBoost;
use Minds\Core\Boost\Elastic\Repository as ElasticBoostRepository;
use Minds\Core\Util\BigNumber;

class ElasticRepository
{
    /** @var ElasticBoostRepository $elasticBoostRepository */
    protected $elasticBoostRepository;

    /**
     * ElasticRepository constructor.
     * @param ElasticBoostRepository $elasticBoostRepository
     */
    public function __construct(
        $elasticBoostRepository = null
    )
    {
        $this->elasticBoostRepository = $elasticBoostRepository ?: new ElasticBoostRepository();
    }

    /**
     * Return a list of boosts
     * @param array $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'is_campaign' => false,
        ], $opts);

        $response = $this->elasticBoostRepository->getList($opts);

        return $response->map(function (RawElasticBoost $rawElasticBoost) {
            $boost = new Boost();
            $boost
                ->setGuid($rawElasticBoost->getGuid())
                ->setEntityGuid($rawElasticBoost->getEntityGuid())
                ->setOwnerGuid($rawElasticBoost->getOwnerGuid())
                ->setCreatedTimestamp($rawElasticBoost->getCreatedTimestamp())
                ->setReviewedTimestamp($rawElasticBoost->getReviewedTimestamp() ?? null)
                ->setRevokedTimestamp($rawElasticBoost->getRevokedTimestamp() ?? null)
                ->setRejectedTimestamp($rawElasticBoost->getRejectedTimestamp() ?? null)
                ->setCompletedTimestamp($rawElasticBoost->getCompletedTimestamp() ?? null)
                ->setPriority((bool) $rawElasticBoost->isPriority())
                ->setType($rawElasticBoost->getType())
                ->setRating($rawElasticBoost->getRating())
                ->setImpressions($rawElasticBoost->getImpressions())
                ->setImpressionsMet($rawElasticBoost->getImpressionsMet())
                ->setBid($rawElasticBoost->getBid())
                ->setBidType($rawElasticBoost->getBidType());

            return $boost;
        });
    }

    /**
     * Return a single boost via urn
     * @param string $urn
     * @return Boost
     */
    public function get($urn)
    {
        return $this->getList([])[0];
    }

    /**
     * Add a boost
     * @param Boost $boost
     * @return bool
     * @throws Exception
     */
    public function add($boost)
    {
        $rawElasticBoost = new RawElasticBoost();

        $rawElasticBoost
            ->setGuid($boost->getGuid())
            ->setOwnerGuid($boost->getOwnerGuid())
            ->setType($boost->getType())
            ->setEntityGuid($boost->getEntityGuid())
            ->setBid(
                $boost->getBidType() === 'tokens' ?
                    (string) BigNumber::fromPlain($boost->getBid(), 18)->toDouble() :
                    $boost->getBid()
            )
            ->setBidType($boost->getBidType())
            ->setPriority((bool) $boost->isPriority())
            ->setRating($boost->getRating())
            ->setImpressions($boost->getImpressions())
            ->setImpressionsMet($boost->getImpressionsMet())
            ->setCreatedTimestamp($boost->getCreatedTimestamp())
            ->setReviewedTimestamp($boost->getReviewedTimestamp())
            ->setRevokedTimestamp($boost->getRevokedTimestamp())
            ->setRejectedTimestamp($boost->getRejectedTimestamp())
            ->setCompletedTimestamp($boost->getCompletedTimestamp());

        if ($boost->getBidType() === 'tokens') {
            $rawElasticBoost->setTokenMethod(
                (strpos($boost->getTransactionId(), '0x', 0) === 0) ?
                    'onchain' :
                    'offchain'
            );
        }

        return $this->elasticBoostRepository->add($rawElasticBoost);
    }

    /**
     * Update a boost
     * @param Boost $boost
     * @return bool
     * @throws Exception
     */
    public function update($boost, $fields = [])
    {
        return $this->add($boost);
    }

    /**
     * void
     */
    public function delete($boost)
    {
    }

}

