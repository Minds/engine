<?php
/**
 * Repository
 * @author edgebal
 */

namespace Minds\Core\Boost\Elastic;

use Minds\Common\Repository\Response;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Data\ElasticSearch\Prepared\Update;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use NotImplementedException;

class Repository
{
    /** @var ElasticSearchClient */
    protected $elasticsearch;

    /**
     * Repository constructor.
     * @param ElasticSearchClient $elasticsearch
     */
    public function __construct(
        $elasticsearch = null
    )
    {
        $this->elasticsearch = $elasticsearch ?: Di::_()->get('Database\ElasticSearch');
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = [])
    {
        $opts = array_merge([
            'rating' => 3,
            'token' => 0,
            'offset' => null,
            'sort' => 'asc',
            'is_campaign' => null,
        ], $opts);

        $must = [];
        $must_not = [];
        $sort = [ '@timestamp' => $opts['sort'] ?: 'asc' ];

        $must[] = [
            'term' => [
                'bid_type' => 'tokens',
            ],
        ];

        if ($opts['is_campaign'] !== null) {
            $must[] = [
                'term' => [
                    'is_campaign' => (bool) $opts['is_campaign'],
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

        if ($opts['guid']) {
            $must[] = [
                'term' => [
                    '_id' => (string) $opts['guid'],
                ],
            ];
        }

        if ($opts['owner_guid']) {
            $must[] = [
                'term' => [
                    'owner_guid' => (string) $opts['owner_guid'],
                ],
            ];
        }

        if ($opts['entity_guid']) {
            $must[] = [
                'term' => [
                    'entity_guid' => $opts['entity_guid']
                ]
            ];
        }

        if ($opts['state'] === 'approved') {
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
        }

        if ($opts['state'] === 'review') {
            $must_not[] = [
                'exists' => [
                    'field' => '@reviewed',
                ],
            ];

            $sort = ['@timestamp' => 'asc'];

            $opts['sort'] = 'asc';
        }

        if ($opts['state'] === 'approved' || $opts['state'] === 'review') {
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

        if ($opts['offset']) {
            $rangeKey = $opts['sort'] === 'asc' ? 'gt' : 'lt';

            $must[] = [
                'range' => [
                    '@timestamp' => [
                        $rangeKey => $opts['offset'],
                    ],
                ],
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

        $prepared = new Search();

        $prepared->query([
            'index' => 'minds-boost',
            'type' => '_doc',
            'body' => $body,
            'size' => $opts['limit'],
            'from' => (int) $opts['token'],
        ]);

        $result = $this->elasticsearch->request($prepared);

        $response = new Response();

        $offset = 0;

        foreach ($result['hits']['hits'] as $doc) {
            $boost = new RawElasticBoost();
            $boost
                ->setGuid($doc['_id'])
                ->setOwnerGuid($doc['_source']['owner_guid'])
                ->setType($doc['_source']['type'])
                ->setEntityGuid($doc['_source']['entity_guid'])
                ->setEntityUrns($doc['_source']['entity_urns'])
                ->setBid($doc['_source']['bid'])
                ->setBidType($doc['_source']['bid_type'])
                ->setTokenMethod($doc['_source']['token_method'] ?? null)
                ->setPriority((bool) $doc['_source']['priority'] ?? false)
                ->setRating($doc['_source']['rating'])
                ->setImpressions($doc['_source']['impressions'])
                ->setImpressionsMet($doc['_source']['impressions_met'])
                ->setCampaign((bool) $doc['_source']['is_campaign'] ?? false)
                ->setCampaignName($doc['_source']['campaign_name'] ?? null)
                ->setCampaignStart($doc['_source']['campaign_start'] ?? null)
                ->setCampaignEnd($doc['_source']['campaign_end'] ?? null)
                ->setCreatedTimestamp($doc['_source']['@timestamp'])
                ->setReviewedTimestamp($doc['_source']['@reviewed'] ?? null)
                ->setRevokedTimestamp($doc['_source']['@revoked'] ?? null)
                ->setRejectedTimestamp($doc['_source']['@rejected'] ?? null)
                ->setCompletedTimestamp($doc['_source']['@completed'] ?? null);

            $response[] = $boost;

            $offset = $boost->getCreatedTimestamp();
        }

        $response->setPagingToken($offset);

        return $response;
    }

    /**
     * @param RawElasticBoost $rawElasticBoost
     * @return bool
     */
    public function add(RawElasticBoost $rawElasticBoost)
    {
        $body = [
            'doc' => [
                'owner_guid' => $rawElasticBoost->getOwnerGuid(),
                'type' => $rawElasticBoost->getType(),
                'entity_guid' => $rawElasticBoost->getEntityGuid(),
                'entity_urns' => $rawElasticBoost->getEntityUrns(),
                'bid' => $rawElasticBoost->getBid(),
                'bid_type' => $rawElasticBoost->getBidType(),
                'priority' => (bool) $rawElasticBoost->isPriority(),
                'rating' => $rawElasticBoost->getRating(),
                'impressions' => $rawElasticBoost->getImpressions(),
                'tags' => $rawElasticBoost->getTags() ?: [],
                'is_campaign' => $rawElasticBoost->isCampaign(),
                'campaign_name' => $rawElasticBoost->getCampaignName(),
                'campaign_start' => $rawElasticBoost->getCampaignStart(),
                'campaign_end' => $rawElasticBoost->getCampaignEnd(),
                '@timestamp' => $rawElasticBoost->getCreatedTimestamp(),
            ],
            'doc_as_upsert' => true,
        ];

        if ($rawElasticBoost->getTokenMethod()) {
            $body['doc']['token_method'] = $rawElasticBoost->getTokenMethod();
        }

        if ($rawElasticBoost->getImpressionsMet()) {
            $body['doc']['impressions_met'] = $rawElasticBoost->getImpressionsMet();
        }

        if ($rawElasticBoost->getReviewedTimestamp()) {
            $body['doc']['@reviewed'] = $rawElasticBoost->getReviewedTimestamp();
        }

        if ($rawElasticBoost->getRevokedTimestamp()) {
            $body['doc']['@revoked'] = $rawElasticBoost->getRevokedTimestamp();
        }

        if ($rawElasticBoost->getRejectedTimestamp()) {
            $body['doc']['@rejected'] = $rawElasticBoost->getRejectedTimestamp();
        }

        if ($rawElasticBoost->getCompletedTimestamp()) {
            $body['doc']['@completed'] = $rawElasticBoost->getCompletedTimestamp();
        }

        $prepared = new Update();

        $prepared->query([
            'index' => 'minds-boost',
            'type' => '_doc',
            'body' => $body,
            'id' => $rawElasticBoost->getGuid(),
        ]);

        return (bool) $this->elasticsearch->request($prepared);
    }

    public function update(RawElasticBoost $rawElasticBoost)
    {
        return $this->add($rawElasticBoost);
    }

    /**
     * @param RawElasticBoost $rawElasticBoost
     * @throws NotImplementedException
     */
    public function delete(RawElasticBoost $rawElasticBoost)
    {
        throw new NotImplementedException();
    }
}

