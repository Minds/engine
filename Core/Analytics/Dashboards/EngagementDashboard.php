<?php
/**
 * Engagement Dashboard
 */
namespace Minds\Core\Analytics\Dashboards;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method EngagementDashboard setTimespanId(string $timespanId)
 * @method EngagementDashboard setFilterIds(array $filtersIds)
 * @method EngagementDashboard setUser(User $user)
 */
class EngagementDashboard implements DashboardInterface
{
    use MagicAttributes;

    /** @var string */
    private $timespanId = '30d';

    /** @var string[] */
    private $filterIds = [ 'platform::browser' ];

    /** @var string */
    private $metricId = 'votes_up';

    /** @var Timespans\TimespansCollection */
    private $timespansCollection;

    /** @var Metrics\MetricsCollection */
    private $metricsCollection;

    /** @var Filters\FiltersCollection */
    private $filtersCollection;

    /** @var User */
    private $user;

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
            ->setUser($this->user)
            ->addFilters(
                new Filters\ChannelFilter()
            );
        $this->metricsCollection
            ->setTimespansCollection($this->timespansCollection)
            ->setFiltersCollection($this->filtersCollection)
            ->setSelectedId($this->metricId)
            ->setUser($this->user)
            ->addMetrics(
                new Metrics\Engagement\VotesUpMetric(),
                new Metrics\Engagement\CommentsMetric(),
                new Metrics\Engagement\RemindsMetric(),
                new Metrics\Engagement\SubscribersMetric(),
                new Metrics\Engagement\ReferralsMetric()
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
            'category' => 'engagement',
            'label' => 'Engagement',
            'description' => '',
            'timespan' => $this->timespansCollection->getSelected()->getId(),
            'timespans' => $this->timespansCollection->export(),
            'metric' => $this->metricsCollection->getSelected()->getId(),
            'metrics' => $this->metricsCollection->export(),
            'filter' => $this->filtersCollection->getSelectedIds(),
            'filters' => $this->filtersCollection->export(),
        ];
    }
}
