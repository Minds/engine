<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Traits\MagicAttributes;

class MetricValues
{
    /** @var int */
    private $current = 0;

    /** @var int */
    private $previous = 0;

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'current' => (int) $current,
            'previous' => (int) $previous,
        ];
    }
}
