<?php
/**
 * PlusFeed
 *
 * @author: Mark Harding
 */
namespace Minds\Core\Search\SortingAlgorithms;

use Minds\Core\Session;

class PlusFeed implements SortingAlgorithm
{
    /**
     * @return bool
     */
    public function isTimestampConstrain(): bool
    {
        return false; // Old period-based algorithms shouldn't be constrained
    }

    /**
     * @param string $period
     * @return $this
     */
    public function setPeriod($period)
    {
        return $this;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        $user = Session::getLoggedInUser();
        if (!$user) {
            return [];
        } else {
            $languages = array_unique([ 'en', $user->getLanguage() ]);
        }
        return [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'language' => $languages
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return string
     */
    public function getScript()
    {
        return "";
    }

    /**
     * @return array
     */
    public function getFunctionScores(): array
    {
        return [
            [
                'field_value_factor' => [
                    'field' => 'votes:up',
                    'factor' => 1,
                    'modifier' => 'sqrt',
                    'missing' => 0,
                ],
            ],
            [
                'field_value_factor' => [
                    'field' => 'comments:count',
                    'factor' => 2,
                    'modifier' => 'sqrt',
                    'missing' => 0,
                ],
            ],
            [
                'filter' => [
                    'range' => [
                        '@timestamp' => [
                            'gte' => 'now-12h',
                        ]
                    ],
                ],
                'weight' => 4,
            ],
            [
                'filter' => [
                    'range' => [
                        '@timestamp' => [
                            'lt' => 'now-12h',
                            'gte' => 'now-36h',
                        ]
                    ],
                ],
                'weight' => 2,
            ],
            [
                'gauss' => [
                    '@timestamp' => [
                        'offset' => '12h', // Do not decay until we reach this bound
                        'scale' => '24h', // Peak decay will be here
                        'decay' => 0.9
                    ],
                ],
                'weight' => 20,
            ]
        ];
    }

    /**
     * @return array
     */
    public function getSort()
    {
        return [
            '_score' => [
                'order' => 'desc'
            ]
        ];
    }

    /**
     * @param array $doc
     * @return int|float
     */
    public function fetchScore($doc)
    {
        return $doc['_score'];
    }

    /**
     * @return string
     */
    public function getScoreMode(): string
    {
        return "multiply";
    }
}
