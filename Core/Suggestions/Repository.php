<?php
/**
 */
namespace Minds\Core\Suggestions;

use Minds\Core\Di\Di;
use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Prepared\Search as Prepared;
use Minds\Core\Data\ElasticSearch\Client;

class Repository
{
    /** @var Client */
    private $es;

    /** @var Config */
    protected $config;

    public function __construct($es = null, Config $config = null)
    {
        $this->es = $es ?: Di::_()->get('Database\ElasticSearch');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Return a list
     * @param array $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => 0,
            'user_guid' => null,
            'user_guids' => null,
            'paging-token' => '',
            'allowFallback' => false,
        ], $opts);

        if ($opts['offset']) {
            $opts['limit'] += $opts['offset'];
        }

        $must = [ ];
        $must_not = [];

        if ($opts['user_guids'] && $opts['type'] === 'user') {
            $must[]['terms'] = [
                'entity_guid.keyword' => $opts['user_guids'],
            ];
        } elseif ($opts['user_guids'] && $opts['type'] === 'group') {
            $must[]['terms'] = [
                'user_guid.keyword' => $opts['user_guids'],
            ];
        } elseif ($opts['user_guid']) { // Terms lookup against minds-graph:subscrpitions
            $must[]['terms'] = [
                'user_guid.keyword' => [
                    'index' => 'minds-graph-subscriptions',
                    'id' => $opts['user_guid'],
                    'path' => 'guids',
                ],
            ];
        }

        if ($opts['type'] === 'group') {
            // Check join (group) action
            $must[]['term'] = [
                'action.keyword' => 'join',
            ];

            // Remove groups we are in
            $must_not[]['terms'] = [
                'entity_guid.keyword' => [
                    'index' => $this->config->get('elasticsearch')['indexes']['search_prefix'] . '-user',
                    'id' => $opts['user_guid'],
                    'path' => 'group_membership',
                ],
            ];
        }

        if ($opts['type'] === 'user') {
            // Check subscribers action
            $must[]['term'] = [
                'action.keyword' => 'subscribe',
            ];

            if ($opts['user_guid']) {
                // Remove everyone we are subscribe to already
                $must_not[]['terms'] = [
                    'entity_guid.keyword' => [
                        'index' => 'minds-graph-subscriptions',
                        'id' => $opts['user_guid'],
                        'path' => 'guids',
                    ],
                ];

                // Remove ourselves
                $must_not[]['term'] = [
                    'entity_guid.keyword' => $opts['user_guid'],
                ];

                // Range
                $must[]['range'] = [
                    '@timestamp' => [
                        'gte' => strtotime('midnight -30 days', time()) * 1000,
                        'lt' => strtotime('midnight', time()) * 1000,
                    ],
                ];
            }
        }

        if ($opts['user_guid']) {
            // Remove everyone we have passed
            $must_not[]['terms'] = [
                'entity_guid.keyword' => [
                    'index' => 'minds-graph-pass',
                    'id' => $opts['user_guid'],
                    'path' => 'guids',
                ],
            ];

            // Remove Minds channel
            $must_not[]['term'] = [
                'user_guid.keyword' => '100000000000000519',
            ];
        }

        $query = [
            'index' => 'minds-metrics-*',
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'must_not' => $must_not,
                    ],
                ],
                'aggs' => [
                    'subscriptions' => [
                        'terms' => [
                            'field' => 'entity_guid.keyword',
                            'size' => $opts['limit'],
                            'order' => [
                                '_count' =>  'desc',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        $prepared = new Prepared();
        $prepared->query($query);

        $result = $this->es->request($prepared);

        $response = new Response();

        foreach ($result['aggregations']['subscriptions']['buckets'] as $i => $row) {
            if ($i < $opts['offset'] -1 || count($response) >= $opts['limit'] - $opts['offset']) {
                continue;
            }
            $suggestion = new Suggestion();
            $suggestion->setConfidenceScore($row['doc_count'])
                ->setEntityGuid($row['key'])
                ->setEntityType('user');
            $response[] = $suggestion;
        }
        
        return $response;
    }

    /**
     * Return a single suggestion
     */
    public function get($guid)
    {
        // Not implemented
    }

    public function add($suggestion)
    {
        // Not implemented
    }

    public function update($suggestion)
    {
        // Not implemented
    }


    public function delete($suggestion)
    {
        // Not implemented
    }
}
