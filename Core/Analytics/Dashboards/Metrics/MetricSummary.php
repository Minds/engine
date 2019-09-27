<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Traits\MagicAttributes;

/**
 * @method MetricSummary setValue(int $value)
 * @method MetricSummary setComparisonValue(int $value)
 * @method MetricSummary setComparisonInterval(int $interval)
 */
class MetricSummary
{
    use MagicAttributes;

    /** @var int */
    private $value = 0;

    /** @var int */
    private $comparisonValue = 0;

    /** @var int */
    private $comparisonInterval = 1;

    /** @var bool */
    private $comparisonPositivity = true;

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'current_value' => (int) $this->value,
            'comparison_value' => (int) $this->comparisonValue,
            'comparison_interval' => (int) $this->comparisonInterval,
            'comparison_positive_inclination' => (bool) $this->comparisonPositivity,
        ];
    }
}
