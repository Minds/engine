<?php
namespace Minds\Core\Channels\Groups\Repository;

/**
 * Class ElasticSearchQuery
 * @package Minds\Core\Channels\Groups\Repository
 */
class ElasticSearchQuery
{
    /**
     * Builds the ES Query
     * @param string $userGuid
     * @param string $searchQuery
     * @return array
     */
    public function build(string $userGuid, string $searchQuery = ''): array
    {
        $query = [
            'index' => 'minds_badger',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'type' => 'group'
                                ]
                            ],
                            [
                                'term' => [
                                    'membership' => 2
                                ]
                            ],
                            [
                                'terms' => [
                                    'guid' => [
                                        'index' => 'minds_badger',
                                        'type' => 'user',
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
