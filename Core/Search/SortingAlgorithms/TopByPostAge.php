<?php
/**
 * TopByPostAge
 *
 * @author: Emiliano Balbuena <edgebal>
 */
namespace Minds\Core\Search\SortingAlgorithms;

class TopByPostAge implements SortingAlgorithm
{
    protected $period;

    /**
     * @return bool
     */
    public function isTimestampConstrain(): bool
    {
        return true;
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
                        'exists' => [
                            'field' => "votes:up",
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
        return "
            def up = (doc['votes:up'].value ?: 0) * 1.0;
            def down = (doc['votes:down'].value ?: 0) * 1.0;
            def magnitude = up + down;
            
            if (magnitude <= 0) {
                return -10;
            }
            
            def score = ((up + 1.9208) / (up + down) - 1.96 * Math.sqrt((up * down) / (up + down) + 0.9604) / (up + down)) / (1 + 3.8416 / (up + down));
            
            return score;
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
