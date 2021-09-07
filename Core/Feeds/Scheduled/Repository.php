<?php

namespace Minds\Core\Feeds\Scheduled;

use Minds\Core\Data\ElasticSearch\Client as ElasticsearchClient;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Di\Di;
use Minds\Helpers\Text;

class Repository
{
    /** @var ElasticsearchClient */
    protected $client;

    protected $index;

    public function __construct($client = null, $config = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');

        $config = $config ?: Di::_()->get('Config');

        $this->index = $config->get('elasticsearch')['indexes']['search_prefix'];
    }

    public function getScheduledCount(array $opts = [])
    {
        $opts = array_merge([
            'container_guid' => null,
            'type' => null,
            'owner_guid' => null,
        ], $opts);

        if (!$opts['type']) {
            throw new \Exception('Type must be provided');
        }

        if (!$opts['container_guid']) {
            throw new \Exception('Container Guid must be provided');
        }

        $containerGuids = Text::buildArray($opts['container_guid']);
        $query = [
            'index' => $this->index . '-*',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    '@timestamp' => [
                                        'gt' => time() * 1000,
                                    ]
                                ]
                            ],
                            [
                                'terms' => [
                                    'container_guid' => $containerGuids,
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($opts['owner_guid']) {
            $ownerGuids = Text::buildArray($opts['owner_guid']);

            $query['body']['query']['bool']['must'][] = [
                'terms' => [
                    'owner_guid' => $ownerGuids,
                ],
            ];
        }

        $prepared = new Prepared\Count();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        return $result['count'] ?? 0;
    }
}
