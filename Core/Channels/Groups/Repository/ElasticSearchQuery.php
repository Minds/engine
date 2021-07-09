<?php
namespace Minds\Core\Channels\Groups\Repository;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

/**
 * Class ElasticSearchQuery
 * @package Minds\Core\Channels\Groups\Repository
 */
class ElasticSearchQuery
{
    /** @var Config */
    protected $config;

    public function __construct(Config $config = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Builds the ES Query
     * @param string $userGuid
     * @param string $searchQuery
     * @return array
     */
    public function build(string $userGuid, string $searchQuery = ''): array
    {
        $indexPrefix = $this->config->get('elasticsearch')['indexes']['search_prefix'];
        $index = $indexPrefix . '-group';
        $query = [
            'index' => $index,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'membership' => 2
                                ]
                            ],
                            [
                                'terms' => [
                                    'guid' => [
                                        'index' => $indexPrefix . '-user',
                                        'id' => $userGuid,
                                        'path' => 'group_membership',
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($searchQuery) {
            $query['body']['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $searchQuery,
                    'operator' => 'OR',
                    'fields' => ['name^10', 'brief_description'],
                ],
            ];
        }

        return $query;
    }
}
