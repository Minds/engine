<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Core\Analytics\Dashboards\Timespans\TimespansCollection;
use Minds\Core\Analytics\Dashboards\Filters\FiltersCollection;
use Minds\Core\Analytics\Dashboards\DashboardCollectionInterface;

class MetricsCollection implements DashboardCollectionInterface
{
    /** @var AbstractMetric[] */
    private $metrics = [];

    /** @var string */
    private $selectedId;

    /** @var TimespansCollection */
    private $timespansCollection;

    /** @var FiltersCollection */
    private $filtersCollection;

    /** @var User */
    private $user;

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
     * @param User $user
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
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
     * @param AbstractMetric[] $metric
     * @return self
     */
    public function addMetrics(AbstractMetric ...$metrics): self
    {
        foreach ($metrics as $metric) {
            if (
                in_array('admin', $metric->getPermissions(), true)
                && !$this->user->isAdmin()
                && !in_array('user', $metric->getPermissions(), true)
            ) {
                continue;
            }
            $metric->setUser($this->user);
            $this->metrics[$metric->getId()] = $metric;
        }
        return $this;
    }

    /**
     * Return the selected metric
     * @return AbstractMetric
     */
    public function getSelected(): AbstractMetric
    {
        if (!isset($this->metrics[$this->selectedId])) {
            $this->selectedId = key($this->metrics);
        }
        return $this->metrics[$this->selectedId];
    }

    /**
     * Return the set metrics
     * @return AbstractMetric[]
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Build the metrics
     * @return self
     */
    public function build(): self
    {
        // Build all summaries
        $this->buildSummaries();

        // Build current visualisation
        $this->getSelected()->buildVisualisation();

        return $this;
    }

    public function buildSummaries(): self
    {
        foreach ($this->metrics as $metric) {
            $metric
                ->setTimespansCollection($this->timespansCollection)
                ->setFiltersCollection($this->filtersCollection)
                ->buildSummary();
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
