<?php

namespace Minds\Core\Recommendations\Algorithms;

use Minds\Core\Search\SortingAlgorithms\SortingAlgorithm;

class WiderNetworkRecommendationsAlgorightm implements SortingAlgorithm
{
    protected string $period;

    /**
     * @inheritDoc
     */
    public function isTimestampConstrain(): bool
    {
        return false;
    }

    /**
     * @param string $period
     * @return $this
     */
    public function setPeriod($period)
    {
        $this->period = $period;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        // TODO: Implement getQuery() method.
    }

    /**
     * @inheritDoc
     */
    public function getScript()
    {
        // TODO: Implement getScript() method.
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
     * @inheritDoc
     */
    public function getFunctionScores(): ?array
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getScoreMode(): string
    {
        return "sum";
    }
}
