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
     * @return bool
     */
    public function isTimestampConstrain(): bool;

    /**
     * @param string $period
     * @return $this
     */
    public function setPeriod($period);

    /**
     * @return array
     */
    public function getQuery();

    /**
     * @return string
     */
    public function getScript();

    /**
     * @return array
     */
    public function getSort();

    /**
     * @param array $doc
     * @return int|float
     */
    public function fetchScore($doc);
}
