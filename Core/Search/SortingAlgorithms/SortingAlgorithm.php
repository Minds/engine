<?php
/**
 * SortingAlgorithm
 *
 * @author: Emiliano Balbuena <edgebal>
 */
namespace Minds\Core\Search\SortingAlgorithms;

interface SortingAlgorithm
{
    /**
     * @param string $period
     * @return static
     */
    public function setPeriod($period);

    /**
     * @return bool
     */
    public function shouldConstraintByTimestamp(): bool;

    /**
     * @return array
     */
    public function getQuery(): array;

    /**
     * @return string
     */
    public function getScript(): string;

    /**
     * @return array
     */
    public function getSort(): array;

    /**
     * @param array $doc
     * @return float
     */
    public function fetchScore($doc): float;
}
