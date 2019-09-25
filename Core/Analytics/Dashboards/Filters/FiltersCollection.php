<?php
namespace Minds\Core\Analytics\Dashboards\Filters;

use Minds\Core\Analytics\Dashboards\DashboardCollectionInterface;

class FiltersCollection implements DashboardCollectionInterface
{
    /** @var FilterAbstract[] */
    private $filters = [];

    /** @var string[] */
    private $selectedIds;

    /**
     * Set the selected metric id
     * @param string[]
     * @return self
     */
    public function setSelectedIds(array $selectedIds): self
    {
        $this->selectedIds = $selectedIds;
        return $this;
    }

    public function getSelected(): array
    {
        // Filters have scoped key pairs like
        // key::value
        // platform::browser
        $selected = [];
        foreach ($this->selectedIds as $selectedId) {
            list($key, $value) = explode('::', $selectedId);
            if (!isset($this->filters[$key])) {
                continue;
            }
            $selected[$key] = $this->filters[$key];
            $selected[$key]->selectOption($value);
        }
        return $selected;
    }

    /**
     * Set the filters
     * @param FilterAbstract[] $filters
     * @return self
     */
    public function addFilters(FilterAbstract ...$filters): self
    {
        foreach ($filters as $filter) {
            $this->filters[$filter->getId()] = $filter;
        }
        return $this;
    }

    /**
     * Return the set metrics
     * @return FilterAbstract[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    // public function build(): self
    // {
    //     foreach ($this->filters as $filter) {
    //         $filter->build();
    //     }
    //     return $this;
    // }

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        $export = [];
        foreach ($this->filters as $filter) {
            $export[] = $filter->export();
        }
        return $export;
    }
}
