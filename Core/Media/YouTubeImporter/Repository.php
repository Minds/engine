<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Media\YouTubeImporter;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Count;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Entity;
use Minds\Entities\User;

/**
 * YouTube Importer Repository
 * @package Minds\Core\Media\YouTubeImporter
 */
class Repository
{
    const ALLOWED_STATUSES = ['queued', 'transcoding', 'completed'];

    /** @var Client */
    protected $client;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(Client $client = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * Returns saved videos
     * @param array $opts
     * @return Response
     * @throws \Exception
     */
    public function getList(array $opts): Response
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => 0,
            'user_guid' => null,
            'youtube_id' => null,
            'status' => null,
            'time_created' => [
                'lt' => null,
                'gt' => null,
            ],
        ], $opts);

        $filter = [];

        if (isset($opts['status'])) {
            if (!in_array($opts['status'], static::ALLOWED_STATUSES, true)) {
                throw new \Exception('Invalid status param');
            }

            $filter[] = [
                'term' => [
                    'transcoding_status' => $opts['status'],
                ],
            ];
        }

        $timeCreatedRange = [];

        if (isset($opts['time_created'])) {
            if (isset($opts['time_created']['lt'])) {
                $timeCreatedRange['lt'] = $opts['time_created']['lt'];
            }

            if (isset($opts['time_created']['gt'])) {
                $timeCreatedRange['gt'] = $opts['time_created']['gt'];
            }
        }

        if (isset($opts['youtube_id'])) {
            $filter[] = [
                'term' => [
                    'youtube_id' => $opts['youtube_id'],
                ],
            ];
        }

        if (count($timeCreatedRange) > 0) {
            $filter[]['range'] = [
                'time_created' => [
                    $timeCreatedRange,
                ],
            ];
        }

        $query = [
            'index' => 'minds_badger',
            'type' => 'object:video',
            'size' => $opts['limit'],
            'from' => $opts['offset'],
            'body' => [
                'query' => [
                    'bool' => [
                        'filter' => $filter,
                    ],
                ],
            ],
        ];

        $response = new Response();

        $prepared = new Search();
        $prepared->query($query);
        try {
            $result = $this->client->request($prepared);

            if (!isset($result) || !(isset($result['hits'])) || !isset($result['hits']['hits']) || count($result['hits']['hits']) === 0) {
                return $response;
            }

            $guids = [];
            foreach ($result['hits']['hits'] as $entry) {
                $guids[] = $entry['_source']['guid'];
            }

            $response = new Response($this->entitiesBuilder->get(['guid' => $guids]));
            $response->setPagingToken((int) $opts['offset'] + (int) $opts['limit']);
        } catch (\Exception $e) {
            error_log('[YouTubeImporter\Repository]' . $e->getMessage());
        }

        return $response;
    }

    public function getCount(User $user): array
    {
        $query = [
            'index' => 'minds_badger',
            'type' => 'object:video',
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'filter' => [
                            [
                                'term' => [
                                    'owner_guid' => $user->getGUID(),
                                ],
                            ],
                        ],
                    ],
                ],
                'aggs' => [
                    'counts' => [
                        'terms' => [
                            'field' => 'transcoding_status',
                        ],
                    ],
                ],
            ],
        ];

        $prepared = new Search();
        $prepared->query($query);
        $result = $this->client->request($prepared);

        $response = [
            'queued' => 0,
            'transferring' => 0,
            'completed' => 0,
        ];

        if ($result['aggregations']['counts']['buckets']) {
            foreach ($result['aggregations']['counts']['buckets'] as $bucket) {
                $key = in_array($bucket['key'], ['queued', 'completed'], true) ? $bucket['key'] : 'transferring';

                $response[$key] = $bucket['doc_count'];
            }
        }

        return $response;
    }

    /**
     * @param array $guids
     * @return array
     */
    public function getOwnersEligibility(array $guids): array
    {
        $result = [];

        foreach ($guids as $guid) {
            /* check for all transcoded videos created in a 24 hour
             * period that correspond to a youtube video */
            $filter = [
                [
                    'range' => [
                        'time_created' => [
                            'lt' => time(),
                            'gte' => strtotime('-10 day'),
                        ],
                    ],
                ],
                [
                    'exists' => [
                        'field' => 'youtube_id',
                    ],
                ],
                [
                    'term' => [
                        'transcoding_status' => 'completed',
                    ],
                ],
                [
                    'term' => [
                        'owner_guid' => $guid,
                    ],
                ],
            ];

            $query = [
                'index' => 'minds_badger',
                'type' => 'object:video',
                'body' => [
                    'query' => [
                        'bool' => [
                            'filter' => $filter,
                        ],
                    ],
                ],
            ];

            $prepared = new Count();
            $prepared->query($query);

            $response = $this->client->request($prepared);

            $count = $response['count'] ?? 0;
            $result[$guid] = $count;
        }

        return $result;
    }
}
