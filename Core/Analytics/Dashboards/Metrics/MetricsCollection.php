<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;
use Minds\Core\Analytics\Dashboards\Timespans\TimespansCollection;
use Minds\Core\Analytics\Dashboards\DashboardCollectionInterface;

class MetricsCollection implements DashboardCollectionInterface
{
    /** @var MetricsAbstract[] */
    private $metrics = [];

    /** @var string */
    private $selectedId;

    /** @var TimespansCollection */
    private $timespansCollection;

    /** @var FiltersCollection */
    private $filtersCollection;

    /**
     * @param TimespansCollection $timespansCollection
     * @return self
     */
    public function setTimespansCollection(TimespansCollection $timespansCollection): self
    {
        $this->timespansCollection = $timespansCollection;
        return $this;
    }

    /**
     * @param FiltersCollection $filtersCollection
     * @return self
     */
    public function setFiltersCollection(?FiltersCollection $filtersCollection): self
    {
        $this->filtersCollection = $filtersCollection;
        return $this;
    }

    /**
     * Set the selected metric id
     * @param string
     * @return self
     */
    public function setSelectedId(string $selectedId): self
    {
        $this->selectedId = $selectedId;
        return $this;
    }

    /**
     * Set the metrics
     * @param MetricAbstract[] $metric
     * @return self
     */
    public function addMetrics(MetricAbstract ...$metrics): self
    {
        foreach ($metrics as $metric) {
            $this->metrics[$metric->getId()] = $metric;
        }
        return $this;
    }

    /**
     * Return the set metrics
     * @return MetricAbstract[]
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function build(): self
    {
        foreach ($this->metrics as $metric) {
            $metric
                ->setTimespansCollection($this->timespansCollection)
                ->build();
        }
        return $this;
    }

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        $export = [];
        foreach ($this->metrics as $metric) {
            $export[] = $metric->export();
        }
        return $export;
    }
}
