<?php
/**
 * ElasticRepository
 * @author edgebal
 */

namespace Minds\Core\Boost\Raw;

use Minds\Common\Repository\Response;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Data\ElasticSearch\Prepared\Update;
use Minds\Core\Di\Di;
use NotImplementedException;

class ElasticRepository
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
            $boost = new RawBoost();
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
                ->setTags($doc['_source']['tags'])
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
     * @param RawBoost $rawBoost
     * @return bool
     */
    public function add(RawBoost $rawBoost)
    {
        $body = [
            'doc' => [
                'owner_guid' => $rawBoost->getOwnerGuid(),
                'type' => $rawBoost->getType(),
                'entity_guid' => $rawBoost->getEntityGuid(),
                'entity_urns' => $rawBoost->getEntityUrns(),
                'bid' => $rawBoost->getBid(),
                'bid_type' => $rawBoost->getBidType(),
                'priority' => (bool) $rawBoost->isPriority(),
                'rating' => $rawBoost->getRating(),
                'impressions' => $rawBoost->getImpressions(),
                'tags' => $rawBoost->getTags() ?: [],
                'is_campaign' => $rawBoost->isCampaign(),
                'campaign_name' => $rawBoost->getCampaignName(),
                'campaign_start' => $rawBoost->getCampaignStart(),
                'campaign_end' => $rawBoost->getCampaignEnd(),
                '@timestamp' => $rawBoost->getCreatedTimestamp(),
            ],
            'doc_as_upsert' => true,
        ];

        if ($rawBoost->getTokenMethod()) {
            $body['doc']['token_method'] = $rawBoost->getTokenMethod();
        }

        if ($rawBoost->getImpressionsMet()) {
            $body['doc']['impressions_met'] = $rawBoost->getImpressionsMet();
        }

        if ($rawBoost->getReviewedTimestamp()) {
            $body['doc']['@reviewed'] = $rawBoost->getReviewedTimestamp();
        }

        if ($rawBoost->getRevokedTimestamp()) {
            $body['doc']['@revoked'] = $rawBoost->getRevokedTimestamp();
        }

        if ($rawBoost->getRejectedTimestamp()) {
            $body['doc']['@rejected'] = $rawBoost->getRejectedTimestamp();
        }

        if ($rawBoost->getCompletedTimestamp()) {
            $body['doc']['@completed'] = $rawBoost->getCompletedTimestamp();
        }

        $prepared = new Update();

        $prepared->query([
            'index' => 'minds-boost',
            'type' => '_doc',
            'body' => $body,
            'id' => $rawBoost->getGuid(),
        ]);

        return (bool) $this->elasticsearch->request($prepared);
    }

    public function update(RawBoost $rawBoost)
    {
        return $this->add($rawBoost);
    }

    /**
     * @param RawBoost $rawBoost
     * @throws NotImplementedException
     */
    public function delete(RawBoost $rawBoost)
    {
        throw new NotImplementedException();
    }
}

