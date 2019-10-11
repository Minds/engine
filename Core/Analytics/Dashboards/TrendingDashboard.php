<?php
/**
 * Trending Dashboard
 */
namespace Minds\Core\Analytics\Dashboards;

use Minds\Traits\MagicAttributes;

/**
 * @method TrafficDashboard setTimespanId(string $timespanId)
 * @method TrafficDashboard setFilterIds(array $filtersIds)
 */
class TrendingDashboard implements DashboardInterface
{
    use MagicAttributes;

    /** @var string */
    private $timespanId = '30d';

    /** @var string[] */
    private $filterIds = [ 'platform::browser' ];

    /** @var string */
    private $metricId = 'views_table';

    /** @var Timespans\TimespansCollection */
    private $timespansCollection;

    /** @var Metrics\MetricsCollection */
    private $metricsCollection;

    /** @var Filters\FiltersCollection */
    private $filtersCollection;

    public function __construct(
        $timespansCollection = null,
        $metricsCollection = null,
        $filtersCollection = null
    ) {
        $this->timespansCollection = $timespansCollection ?? new Timespans\TimespansCollection();
        $this->metricsCollection = $metricsCollection ?? new Metrics\MetricsCollection();
        $this->filtersCollection = $filtersCollection ?? new Filters\FiltersCollection();
    }

    /**
     * Build the dashboard
     * @return self
     */
    public function build(): self
    {
        $this->timespansCollection
            ->setSelectedId($this->timespanId)
            ->addTimespans(
                new Timespans\TodayTimespan(),
                new Timespans\_30dTimespan(),
                new Timespans\_1yTimespan(),
                new Timespans\MtdTimespan(),
                new Timespans\YtdTimespan()
            );
        $this->filtersCollection
            ->setSelectedIds($this->filterIds)
            ->addFilters(
                // new Filters\PlatformFilter(),
                new Filters\ViewTypeFilter(),
                new Filters\ChannelFilter()
            );
        $this->metricsCollection
            ->setTimespansCollection($this->timespansCollection)
            ->setFiltersCollection($this->filtersCollection)
            ->setSelectedId($this->metricId)
            ->addMetrics(
                new Metrics\ViewsTableMetric()
            )
            ->build();

        return $this;
    }

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        $this->build();
        return [
            'category' => 'trending',
            'timespan' => $this->timespansCollection->getSelected()->getId(),
            'timespans' => $this->timespansCollection->export(),
            'metric' => $this->metricsCollection->getSelected()->getId(),
            'metrics' => $this->metricsCollection->export(),
            'filter' => $this->filtersCollection->getSelectedIds(),
            'filters' => $this->filtersCollection->export(),
        ];
    }
}
