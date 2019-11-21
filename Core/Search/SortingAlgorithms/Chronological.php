<?php
/**
 * Chronological
 *
 * @author: Emiliano Balbuena <edgebal>
 */

namespace Minds\Core\Search\SortingAlgorithms;

class Chronological implements SortingAlgorithm
{
    /**
     * @param string $period
     * @return Chronological
     */
    public function setPeriod($period): Chronological
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldConstraintByTimestamp(): bool
    {
        return false;
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
        return '';
    }

    /**
     * @return array
     */
    public function getSort(): array
    {
        return [
            'time_created' => [
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
        return (float) $doc['_source']['time_created'];
    }
}
