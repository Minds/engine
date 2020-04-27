<?php
/**
 * Hot
 *
 * @author: Emiliano Balbuena <edgebal>
 */

namespace Minds\Core\Search\SortingAlgorithms;

use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as Features;

class Hot implements SortingAlgorithm
{
    /** @var Features */
    protected $features;

    /** @var string */
    protected $period;

    public function __construct($features = null)
    {
        $this->features = $features ?? Di::_()->get('Features\Manager');
    }


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
        if (!$this->features->has('top-feeds-by-age')) {
            $this->period = $period;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        if ($this->period) {
            return [
                'bool' => [
                    'must' => [
                        [
                            'range' => [
                                "votes:up:{$this->period}:synced" => [
                                    'gte' => strtotime("7 days ago", time()),
                                ],
                            ],
                        ],
                    ],
                   ]
            ];
        }
        return [
               'bool' => [
                   'must' => [
                       [
                           'range' => [
                               "votes:up" => [
                                   'gte' => 1,
                               ],
                           ],
                       ],
                   ],
               ]
           ];
    }

    /**
     * @return string
     */
    public function getScript()
    {
        $time = time();
        if ($this->period) {
            return "
                def up = doc['votes:up:{$this->period}'].value ?: 0;
                def down = doc['votes:down:{$this->period}'].value ?: 0;

                def age = $time - (doc['@timestamp'].value.millis / 1000) - 1546300800;

                def votes = up - down;
                def sign = (votes > 0) ? 1 : (votes < 0 ? -1 : 0);
                def order = Math.log(Math.max(Math.abs(votes), 1));

                return (sign * order) - (age / 43200);
            ";
        }
        return "
            def up = doc['votes:up'].value ?: 0;
            def down = doc['votes:down'].value ?: 0;

            def age = doc['@timestamp'].value.millis / 1000;

            def votes = up - down;
            def sign = (votes > 0) ? 1 : (votes < 0 ? -1 : 0);
            def order = Math.log(Math.max(Math.abs(votes), 1));

            // Rounds to 7
            return Math.round((sign * order + age / 43200) * 1000000) / 1000000;
        ";
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
     * @return array
     */
    public function getFunctionScores(): ?array
    {
        return null;
    }

    /**
     * @return string
     */
    public function getScoreMode(): string
    {
        return "sum";
    }
}
