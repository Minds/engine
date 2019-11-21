<?php
/**
 * Top
 *
 * @author: Emiliano Balbuena <edgebal>
 */
namespace Minds\Core\Search\SortingAlgorithms;

class Top implements SortingAlgorithm
{
    /**
     * @param string $period
     * @return $this
     */
    public function setPeriod($period): Top
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldConstraintByTimestamp(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return "
            def up = (doc['votes:up'].value ?: 0) * 1.0;
            def down = (doc['votes:down'].value ?: 0) * 1.0;
            def magnitude = up + down;
            
            if (magnitude <= 0) {
                return -10;
            }
            
            return ((up + 1.9208) / (up + down) - 1.96 * Math.sqrt((up * down) / (up + down) + 0.9604) / (up + down)) / (1 + 3.8416 / (up + down));
        ";
    }

    /**
     * @return array
     */
    public function getSort(): array
    {
        return [
            '_score' => [
                'order' => 'desc'
            ]
        ];
    }

    /**
     * @param array $doc
     * @return float
     */
    public function fetchScore($doc): float
    {
        return (float) $doc['_score'];
    }
}
