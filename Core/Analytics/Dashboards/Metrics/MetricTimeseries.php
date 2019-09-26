<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Traits\MagicAttributes;

class MetricTimeseries
{
    /** @var array */
    private $dateHistogram = [];

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'date_histogram' => (array) $this->dateHistogram,
        ];
    }
}
