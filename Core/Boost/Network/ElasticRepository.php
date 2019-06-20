<?php
/**
 * Elasticsearch repository for Boost
 */
namespace Minds\Core\Boost\Network;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Boost\Raw\RawBoost;
use Minds\Core\Boost\Raw\ElasticRepository as RawElasticRepository;
use Minds\Core\Util\BigNumber;

class ElasticRepository
{
    /** @var RawElasticRepository $rawElasticRepository */
    protected $rawElasticRepository;

    /**
     * ElasticRepository constructor.
     * @param RawElasticRepository $rawElasticRepository
     */
    public function __construct(
        $rawElasticRepository = null
    )
    {
        $this->rawElasticRepository = $rawElasticRepository ?: new RawElasticRepository();
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

        $response = $this->rawElasticRepository->getList($opts);

        return $response->map(function (RawBoost $rawBoost) {
            $boost = new Boost();
            $boost
                ->setGuid($rawBoost->getGuid())
                ->setEntityGuid($rawBoost->getEntityGuid())
                ->setOwnerGuid($rawBoost->getOwnerGuid())
                ->setCreatedTimestamp($rawBoost->getCreatedTimestamp())
                ->setReviewedTimestamp($rawBoost->getReviewedTimestamp() ?? null)
                ->setRevokedTimestamp($rawBoost->getRevokedTimestamp() ?? null)
                ->setRejectedTimestamp($rawBoost->getRejectedTimestamp() ?? null)
                ->setCompletedTimestamp($rawBoost->getCompletedTimestamp() ?? null)
                ->setPriority((bool) $rawBoost->isPriority())
                ->setType($rawBoost->getType())
                ->setRating($rawBoost->getRating())
                ->setImpressions($rawBoost->getImpressions())
                ->setImpressionsMet($rawBoost->getImpressionsMet())
                ->setBid($rawBoost->getBid())
                ->setBidType($rawBoost->getBidType());

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
        $rawBoost = new RawBoost();

        $rawBoost
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
            $rawBoost->setTokenMethod(
                (strpos($boost->getTransactionId(), '0x', 0) === 0) ?
                    'onchain' :
                    'offchain'
            );
        }

        return $this->rawElasticRepository->add($rawBoost);
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

