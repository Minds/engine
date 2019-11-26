<?php

namespace Minds\Core\Boost\Campaigns;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Data\ElasticSearch\Prepared\Update;
use Minds\Core\Di\Di;
use Minds\Helpers\Number;
use Minds\Helpers\Text;

class ElasticRepository
{
    /** @var ElasticSearchClient */
    protected $es;

    /** @var ElasticRepositoryQueryBuilder */
    protected $queryBuilder;

    /**
     * Options for fetching queries
     * @var array
     */
    protected $opts;

    public function __construct(
        ?ElasticSearchClient $es = null,
        ?ElasticRepositoryQueryBuilder $queryBuilder = null
    ) {
        $this->es = $es ?: Di::_()->get('Database\ElasticSearch');
        $this->queryBuilder = $queryBuilder ?: new ElasticRepositoryQueryBuilder();
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getCampaigns(array $opts = [])
    {
        $this->opts = array_merge([
            'limit' => 12,
            'from' => 0,
            'type' => ''
        ], $opts);

        $this->queryBuilder->setOpts($opts);

        $prepared = new Search();

        $prepared->query([
            'index' => 'minds-boost-campaigns',
            'type' => $this->opts['type'],
            'body' => $this->queryBuilder->query(),
            'size' => $this->opts['limit'],
            'from' => (int)($this->opts['from'] ?? 0),
        ]);

        $result = $this->es->request($prepared);

        $response = new Response();

        $offset = 0;

        foreach ($result['hits']['hits'] as $doc) {
            $campaign = new Campaign();
            $campaign
                ->setUrn("urn:campaign:{$doc['_id']}")
                ->setType($doc['_source']['type'])
                ->setOwnerGuid($doc['_source']['owner_guid'])
                ->setName($doc['_source']['name'])
                ->setEntityUrns(Text::buildArray($doc['_source']['entity_urns']))
                ->setHashtags(Text::buildArray($doc['_source']['hashtags']))
                ->setNsfw(Number::buildIntArray($doc['_source']['nsfw']))
                ->setStart((int) $doc['_source']['start'])
                ->setEnd((int) $doc['_source']['end'])
                ->setBudget((string) $doc['_source']['budget'])
                ->setBudgetType($doc['_source']['budget_type'])
                ->setChecksum($doc['_source']['checksum'])
                ->setImpressions((int) $doc['_source']['impressions'])
                ->setImpressionsMet($doc['_source']['impressions_met'])
                ->setRating($doc['_source']['rating'])
                ->setQuality($doc['_source']['quality'])
                ->setCreatedTimestamp(($doc['_source']['@timestamp']) ?? null)
                ->setReviewedTimestamp(($doc['_source']['@reviewed']) ?? null)
                ->setRejectedTimestamp(($doc['_source']['@rejected']) ?? null)
                ->setRevokedTimestamp(($doc['_source']['@revoked']) ?? null)
                ->setCompletedTimestamp(($doc['_source']['@completed']) ?? null);

            $response[] = $campaign;
            $offset = $campaign->getCreatedTimestamp();
        }

        $response->setPagingToken($offset);

        return $response;
    }

    public function getCampaignsAndBoosts(array $opts = [])
    {
        $this->opts = array_merge([
            'limit' => 24,
            'from' => 0,
            'type' => ''
        ], $opts);

        $this->queryBuilder->setOpts($this->opts);

        $prepared = new Search();
        $prepared->query([
            'index' => 'minds-boost,minds-boost-campaigns',
            'type' => $this->opts['type'],
            'body' => $this->queryBuilder->query(),
            'from' => $this->opts['from'] ?? 0,
            'size' => $this->opts['limit'],
        ]);

        $result = $this->es->request($prepared);

        $data = [];

        foreach ($result['hits']['hits'] as $doc) {
            $entity = null;

            switch ($doc['_index']) {
                case 'minds-boost':
                    $entity = (new Boost())
                        ->setGuid($doc['_id'])
                        ->setOwnerGuid($doc['_source']['owner_guid'])
                        ->setCreatedTimestamp($doc['_source']['@timestamp'])
                        ->setType($doc['_source']['type']);
                    break;
                case 'minds-boost-campaigns':
                    $entity = (new Campaign())
                        ->setUrn("urn:campaign:{$doc['_id']}")
                        ->setType($doc['_source']['type'])
                        ->setOwnerGuid($doc['_source']['owner_guid'])
                        ->setCreatedTimestamp(((int) $doc['_source']['@timestamp']) ?: null);
                    break;
                default:
                    continue 2;
            }

            $data[] = $entity;
        }

        $data = array_slice($data, 0, $this->opts['limit']);

        $response = new Response($data, count($data));

        return $response;
    }

    /**
     * @param Campaign $campaign
     * @return bool
     * @throws Exception
     */
    public function putCampaign(Campaign $campaign)
    {
        $body = [
            'doc' => [
                'type' => $campaign->getType(),
                'owner_guid' => (string) $campaign->getOwnerGuid(),
                'name' => $campaign->getName(),
                'entity_urns' => $campaign->getEntityUrns(),
                'hashtags' => $campaign->getHashtags(),
                'nsfw' => $campaign->getNsfw(),
                'start' => (int) $campaign->getStart(),
                'end' => (int) $campaign->getEnd(),
                'budget' => $campaign->getBudget(),
                'budget_type' => $campaign->getBudgetType(),
                'checksum' => $campaign->getChecksum(),
                'impressions' => $campaign->getImpressions(),
                '@timestamp' => $campaign->getCreatedTimestamp(),
            ],
            'doc_as_upsert' => true,
        ];

        if ($campaign->getImpressionsMet()) {
            $body['doc']['impressions_met'] = $campaign->getImpressionsMet();
        }

        if ($campaign->getRating()) {
            $body['doc']['rating'] = $campaign->getRating();
        }

        if ($campaign->getQuality()) {
            $body['doc']['quality'] = $campaign->getQuality();
        }

        if ($campaign->getReviewedTimestamp()) {
            $body['doc']['@reviewed'] = $campaign->getReviewedTimestamp();
        }

        if ($campaign->getRejectedTimestamp()) {
            $body['doc']['@rejected'] = $campaign->getRejectedTimestamp();
        }

        if ($campaign->getRevokedTimestamp()) {
            $body['doc']['@revoked'] = $campaign->getRevokedTimestamp();
        }

        if ($campaign->getCompletedTimestamp()) {
            $body['doc']['@completed'] = $campaign->getCompletedTimestamp();
        }

        $prepared = new Update();

        $prepared->query([
            'index' => 'minds-boost-campaigns',
            'type' => '_doc',
            'body' => $body,
            'id' => (string) $campaign->getGuid(),
        ]);

        return (bool) $this->es->request($prepared);
    }
}
