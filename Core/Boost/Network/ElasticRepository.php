<?php
/**
 * Elasticsearch repository for Boost
 */
namespace Minds\Core\Boost\Network;

use Minds\Common\Repository\Response;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Di\Di;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Util\BigNumber;

class ElasticRepository
{
    /** @var Client $es */
    protected $es;

    public function __construct($es = null)
    {
        $this->es = $es ?: Di::_()->get('Database\ElasticSearch');
    }

    /**
     * Return a list of boosts
     * @param array $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'rating' => Boost::RATING_OPEN,
            'token' => 0,
            'offset' => null,
            'order' => null,
            'offchain' => null,
            'type' => null,
            'boost_type' => null,
            'paused' => false
        ], $opts);

        $must = [];
        $must_not = [];
        $sort = [ '@timestamp' => $opts['order'] ?? 'asc' ];

        if ($opts['bid_type']) {
            $must[] = [
                'term' => [
                    'bid_type' => $opts['bid_type'],
                ],
            ];
        }

        if ($opts['type']) {
            $must[] = [
                'term' => [
                    'type' => $opts['type'],
                ],
            ];
        }

        if ($opts['offset']) {
            $must[] = [
                'range' => [
                    '@timestamp' => [
                        'gt' => $opts['offset'],
                    ],
                ],
            ];
        }

        if ($opts['entity_guid']) {
            $must[] = [
                'term' => [
                    'entity_guid' => $opts['entity_guid'],
                ],
            ];
        }

        if ($opts['owner_guid']) {
            $must[] = [
                'term' => [
                    'owner_guid' => $opts['owner_guid'],
                ],
            ];
        }

        if ($opts['state'] === Manager::OPT_STATEQUERY_APPROVED) {
            $must[] = [
                'exists' => [
                    'field' => '@reviewed',
                ],
            ];
            $must[] = [
                'range' => [
                    'rating' => [
                        'lte' => $opts['rating'],
                    ],
                ],
            ];
            $must_not[] = [
                'exists' => [
                    'field' => '@completed',
                ],
            ];
            $must_not[] = [
                'exists' => [
                    'field' => '@rejected',
                ],
            ];
            $must_not[] = [
                'exists' => [
                    'field' => '@revoked',
                ],
            ];
        }

        if ($opts['state'] === Manager::OPT_STATEQUERY_REVIEW) {
            $must_not[] = [
                'exists' => [
                    'field' => '@reviewed',
                ],
            ];
            $must_not[] = [
                'exists' => [
                    'field' => '@completed',
                ],
            ];
            $must_not[] = [
                'exists' => [
                    'field' => '@rejected',
                ],
            ];
            $must_not[] = [
                'exists' => [
                    'field' => '@revoked',
                ],
            ];
            $sort = ['@timestamp' => 'asc'];
        }

        if ($opts['state'] === Manager::OPT_STATEQUERY_ACTIVE) {
            $must_not[] = [
                'exists' => [
                    'field' => '@completed',
                ],
            ];
            $must_not[] = [
                'exists' => [
                    'field' => '@rejected',
                ],
            ];
            $must_not[] = [
                'exists' => [
                    'field' => '@revoked',
                ],
            ];
        }

        if ($opts['offchain']) {
            $must[] = [
                'term' => [
                    'token_method' => 'offchain',
                ],
            ];
        }

        if ($opts['boost_type']) {
            $must[] = [
                'term' => [
                    'boost_type' => $opts['boost_type'],
                ],
            ];
        }

        if (empty($opts['paused'])) {
            $must_not[] = [
                'term' => [
                    'paused' => 1,
                ]
            ];
        } else {
            if ($opts['paused'] !== Manager::OPT_PAUSEQUERY_ANY) {
                $must[] = [
                    'term' => [
                        'paused' => intval($opts['paused']),
                    ]
                ];
            }
        }

        if ($opts['guid']) {
            $must[] = [
                'term' => [
                    '_id' => $opts['guid']
                ]
            ];
        }

        $body = [
            'query' => [
                'bool' => [
                    'must' => $must,
                    'must_not' => $must_not,
                ],
            ],
            'sort' => $sort,
        ];

        $prepared = new Prepared\Search();
        $prepared->query([
            'index' => 'minds-boost',
            'type' => '_doc',
            'body' => $body,
            'size' => $opts['limit'],
            'from' => (int) $opts['token'],
        ]);

        $result = $this->es->request($prepared);
        
        $response = new Response;

        $offset = 0;

        foreach ($result['hits']['hits'] as $doc) {
            if (isset($doc['_source']['boost_type'])) {
                $boostType = $doc['_source']['boost_type'];
                if ($boostType === Boost::BOOST_TYPE_CAMPAIGN) {
                    $boost = new Campaign();
                    $boost->setName($doc['_source']['name']);
                    $boost->setStart($doc['_source']['@start']);
                    $boost->setEnd($doc['_source']['@end']);
                    $boost->setBudget($doc['_source']['budget']);
                    $boost->setPaused($doc['_source']['paused']);
                } else {
                    $boost = new Boost();
                }
                $boost->setBoostType($boostType);
            } else {
                $boost = new Boost();
            }

            $boost
                ->setGuid($doc['_id'])
                ->setEntityGuid($doc['_source']['entity_guid'])
                ->setOwnerGuid($doc['_source']['owner_guid'])
                ->setCreatedTimestamp($doc['_source']['@timestamp'])
                ->setReviewedTimestamp($doc['_source']['@reviewed'] ?? null)
                ->setRevokedTimestamp($doc['_source']['@revoked'] ?? null)
                ->setRejectedTimestamp($doc['_source']['@rejected'] ?? null)
                ->setCompletedTimestamp($doc['_source']['@completed'] ?? null)
                ->setPriority($doc['_source']['priority'] ?? false)
                ->setType($doc['_source']['type'])
                ->setRating($doc['_source']['rating'])
                ->setImpressions($doc['_source']['impressions'] ?? 0)
                ->setImpressionsMet($doc['_source']['impressions_met'] ?? 0)
                ->setBid($doc['_source']['bid'])
                ->setBidType($doc['_source']['bid_type']);
            $offset = $boost->getCreatedTimestamp();
            $response[] = $boost;
        }

        $response->setPagingToken($offset);
        return $response;
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
     * @param Boost|Campaign $boost
     * @return bool
     * @throws \Exception
     */
    public function add($boost)
    {
        if (empty($boost->getBid())) {
            $bid = 0;
        } else {
            $bid =  $boost->getBidType() === 'tokens' ?
                (string) BigNumber::fromPlain($boost->getBid(), 18)->toDouble() : $boost->getBid();
        }

        $body = [
            'doc' => [
                '@timestamp' => $boost->getCreatedTimestamp(),
                'bid' => $bid,
                'bid_type' => $boost->getBidType(),
                'entity_guid' => $boost->getEntityGuid(),
                'impressions' => $boost->getImpressions(),
                'owner_guid' => $boost->getOwnerGuid(),
                'rating' => $boost->getRating(),
                'type' => $boost->getType(),
                'boost_type' => $boost->getBoostType(),
            ],
            'doc_as_upsert' => true,
        ];

        if ($boost instanceof Campaign) {
            $body['doc']['name'] = $boost->getName();
            $body['doc']['budget'] = $boost->getBudget();
            $body['doc']['@start'] = $boost->getStart();
            $body['doc']['@end'] = $boost->getEnd();
            $body['doc']['paused'] = $boost->getPaused();
        }

        if ($boost->getBidType() === 'tokens') {
            $body['doc']['token_method'] = (strpos($boost->getTransactionId(), '0x', 0) === 0)
                ? 'onchain' : 'offchain';
        }

        if ($boost->getImpressionsMet()) {
            $body['doc']['impressions_met'] = $boost->getImpressionsMet();
        }

        if ($boost->getCompletedTimestamp()) {
            $body['doc']['@completed'] = $boost->getCompletedTimestamp();
        }

        if ($boost->getReviewedTimestamp()) {
            $body['doc']['@reviewed'] = $boost->getReviewedTimestamp();
        }

        if ($boost->getRevokedTimestamp()) {
            $body['doc']['@revoked'] = $boost->getRevokedTimestamp();
        }

        if ($boost->getRejectedTimestamp()) {
            $body['doc']['@rejected'] = $boost->getRejectedTimestamp();
        }

        $prepared = new Prepared\Update();
        $prepared->query([
            'index' => 'minds-boost',
            'type' => '_doc',
            'body' => $body,
            'id' => $boost->getGuid(),
        ]);

        return (bool) $this->es->request($prepared);
    }

    /**
     * Update a boost
     * @param Boost $boost
     * @return bool
     * @throws \Exception
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
