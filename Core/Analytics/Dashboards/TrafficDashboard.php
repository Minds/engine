<?php
/**
 * Traffic Dashboard
 */
namespace Minds\Core\Analytics\Dashboards;

use Minds\Traits\MagicAttributes;

/**
 * @method TrafficDashboard setTimespanId(string $timespanId)
 * @method TrafficDashboard setFilterIds(array $filtersIds)
 */
class TrafficDashboard implements DashboardInterface
{
    use MagicAttributes;

    /** @var string */
    private $timespanId;

    /** @var string[] */
    private $filterIds;

    /** @var string */
    private $metricId;

    /** @var Timespans\TimespansCollection */
    private $timespansCollection;

    /** @var Metrics\MetricsCollection */
    private $metricsCollection;

    /** @var Filters\FiltersCollection */
    private $filtersCollection;

    /** @var Visualisations\Charts\ChartsCollection */
    private $chartsCollection;

    public function __construct(
        $timespansCollection = null,
        $metricsCollection = null,
        $filtersCollection = null,
        $chartsCollection = null
    ) {
        $this->timespansCollection = $timespansCollection ?? new Timespans\TimespansCollection();
        $this->metricsCollection = $metricsCollection ?? new Metrics\MetricsCollection();
        $this->filtersCollection = $filtersCollection ?? new Filters\FiltersCollection();
        $this->chartsCollection = $chartsCollection ?? new Visualisations\Charts\ChartsCollection();
    }

    /**
     * Build the dashboard
     * @return self
     */
    public function build(): self
    {
        $this->timespansCollection
            ->setSelectedId($this->timespanId)
            ->addTimespan(
                new Timespans\TodayTimespan(),
                new Timespans\MtdTimespan(),
                new Timespans\YtdTimespan()
            );
        $this->filtersCollection
            ->setSelectedIds($this->filterIds)
            ->addFilters(
                new Filters\PlatformFilter(),
                new Filters\ViewTypeFilter()
            );
        $this->metricsCollection
            ->setTimespansCollection($this->timespansCollection)
            ->setFiltersCollection($this->filtersCollection)
            ->setSelectedId($this->metricId)
            ->addMetrics(
                new Metrics\ActiveUsersMetric(),
                new Metrics\SignupsMetric(),
                new Metrics\ViewsMetric()
            )
            ->build();
        $this->chartsCollection
            ->setTimespansCollection($this->timespansCollection)
            ->setMetricsCollection($this->metricsCollection);

        return $this;
    }

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'category' => 'traffic',
            'timespan' => $this->timespansCollection->getSelected->getId(),
            'timespans' => $this->timespansCollection->export(),
            'metrics' => $this->metricsCollection->export(),
            'charts' => $this->chartCollection->export(),
            'filter' => $this->filtersCollection->getSelected->getId(),
            'filters' => $this->filtersCollection->export(),
        ];
    }
}
