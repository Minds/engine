<?php
/**
 * Token Dashboard
 */
namespace Minds\Core\Analytics\Dashboards;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method TokenDashboard setTimespanId(string $timespanId)
 * @method TokenDashboard setFilterIds(array $filtersIds)
 * @method TokenDashboard setUser(User $user)
 */
class TokenDashboard implements DashboardInterface
{
    use MagicAttributes;

    /** @var string */
    private $timespanId = '30d';

    /** @var string[] */
    private $filterIds = [ 'platform::browser' ];

    /** @var string */
    private $metricId = 'token_balance';

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
                new Timespans\_7dTimespan(),
                new Timespans\_30dTimespan(),
                new Timespans\_90dTimespan(),
                new Timespans\_1yTimespan(),
                new Timespans\MaxTimespan()
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
                new Metrics\TokenBalanceMetric()
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
            'category' => 'token',
            'label' => 'Token',
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
