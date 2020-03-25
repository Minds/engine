<?php
/**
 * Top
 *
 * @author: Mark Harding
 */
namespace Minds\Core\Search\SortingAlgorithms;

class TopV2 implements SortingAlgorithm
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
        return [
            'bool' => [
                'must' => [
                    [
                        'range' => [
                            "votes:up:synced" => [
                                'gte' => strtotime("midnight 7 days ago", time()),
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
        $now = time();
        return "
            def up = doc['votes:up'].value ?: 0;
            def down = doc['votes:down'].value ?: 0;
            def comments = doc['comments:count'].value ?: 0;

            def age = $now - (doc['@timestamp'].value.millis / 1000);

			def votes = (comments * 2) + up - down;
            def sign = (votes > 0) ? 1 : (votes < 0 ? -1 : 0);
            def order = Math.log(Math.max(Math.abs(votes), 1));

            // Rounds to 7
            return (sign * order) - (age / 43200);
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
}
