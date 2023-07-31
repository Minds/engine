<?php
namespace Minds\Core\Channels\Groups\Repository;

use Minds\Core\Groups\V2\Membership;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;

/**
 * Class ElasticSearchQuery
 * @package Minds\Core\Channels\Groups\Repository
 */
class ElasticSearchQuery
{
    public function __construct(
        protected ?Config $config = null,
        protected ?Membership\Manager $groupsMembership = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
    ) {
        $this->config = $config ?? Di::_()->get('Config');
        $this->groupsMembership ??= Di::_()->get(Membership\Manager::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    /**
     * Builds the ES Query
     * @param string $userGuid
     * @param string $searchQuery
     * @return array
     */
    public function build(string $userGuid, string $searchQuery = ''): array
    {
        $user = $this->entitiesBuilder->single($userGuid);

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
                                    'guid' => array_map(function ($guid) {
                                        return (string) $guid;
                                    }, $this->groupsMembership->getGroupGuids($user))
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
