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
     * @param array $guids
     * @return array
     */
    public function build(array $guids = []): array
    {
        return [
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
                                    'guid' => $guids
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
