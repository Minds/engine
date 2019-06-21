<?php
/**
 * Cassandra Repository for Boost
 */
namespace Minds\Core\Boost\Network;

use Exception;
use Minds\Common\Urn;
use Minds\Common\Repository\Response;
use Minds\Core\Boost\Raw\RawBoost;
use Minds\Core\Boost\Raw\Repository as RawRepository;
use Minds\Core\Data\Cassandra\Prepared;
use Cassandra;

class Repository
{
    /** @var RawRepository $rawRepository */
    private $rawRepository;

    /** @var Urn $urn */
    private $urn;

    public function __construct($rawRepository = null, $urn = null)
    {
        $this->rawRepository = $rawRepository ?: new RawRepository();
        $this->urn = $urn ?: new Urn(); 
    }

    /**
     * Return a list of boosts
     * @param array $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts['offset'] = $opts['token'] ?? '';

        return $this->rawRepository->getList($opts)
            ->map(function (RawBoost $rawBoost) {
                $boost = new Boost();

                $boost
                    ->setGuid($rawBoost->getGuid())
                    ->setEntityGuid($rawBoost->getEntityGuid())
                    ->setBid($rawBoost->getBid())
                    ->setImpressions($rawBoost->getImpressions())
                    ->setBidType($rawBoost->getBidType())
                    ->setOwnerGuid($rawBoost->getOwnerGuid())
                    ->setTransactionId($rawBoost->getTransactionId())
                    ->setType($rawBoost->getType())
                    ->setPriority($rawBoost->isPriority())
                    ->setRating($rawBoost->getRating())
                    ->setTags($rawBoost->getTags())
                    ->setNsfw($rawBoost->getNsfw())
                    ->setRejectionReason($rawBoost->getRejectionReason())
                    ->setChecksum($rawBoost->getChecksum())
                    ->setCreatedTimestamp($rawBoost->getCreatedTimestamp())
                    ->setReviewedTimestamp($rawBoost->getReviewedTimestamp())
                    ->setRejectedTimestamp($rawBoost->getRejectedTimestamp())
                    ->setRevokedTimestamp($rawBoost->getRevokedTimestamp())
                    ->setCompletedTimestamp($rawBoost->getCompletedTimestamp())
                    ->setMongoId($rawBoost->getMongoId())
                    ->setEntity($rawBoost->getEntity())
                    ->setOwner($rawBoost->getOwner())
                    ->setState($rawBoost->getState());

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
        list($type, $guid) = explode(':', $this->urn->setUrn($urn)->getNss(), 2);
        return $this->getList([
            'type' => $type,
            'guids' => [ $guid ],
        ])[0];
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
            ->setEntityGuid($boost->getEntityGuid())
            ->setBid($boost->getBid())
            ->setImpressions($boost->getImpressions())
            ->setBidType(in_array($boost->getBidType(), [ 'onchain', 'offchain' ]) ? 'tokens' : $boost->getBidType())
            ->setOwnerGuid($boost->getOwnerGuid())
            ->setTransactionId($boost->getTransactionId())
            ->setType($boost->getType())
            ->setPriority($boost->isPriority())
            ->setRating($boost->getRating())
            ->setTags($boost->getTags())
            ->setNsfw($boost->getNsfw())
            ->setRejectionReason($boost->getRejectionReason())
            ->setChecksum($boost->getChecksum())
            ->setCreatedTimestamp($boost->getCreatedTimestamp())
            ->setReviewedTimestamp($boost->getReviewedTimestamp())
            ->setRejectedTimestamp($boost->getRejectedTimestamp())
            ->setRevokedTimestamp($boost->getRevokedTimestamp())
            ->setCompletedTimestamp($boost->getCompletedTimestamp())
            ->setMongoId($boost->getMongoId())
            ->setEntity($boost->getEntity())
            ->setOwner($boost->getOwner())
            ->setState($boost->getState());

        return $this->rawRepository->add($rawBoost);
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

