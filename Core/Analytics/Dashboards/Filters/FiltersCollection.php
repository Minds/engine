<?php
namespace Minds\Core\Analytics\Dashboards\Filters;

use Minds\Entities\User;
use Minds\Core\Analytics\Dashboards\DashboardCollectionInterface;

class FiltersCollection implements DashboardCollectionInterface
{
    /** @var AbstractFilter[] */
    private $filters = [];

    /** @var string[] */
    private $selectedIds;

    /** @var User */
    private $user;

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

    /**
     * Selected ids
     * @return string[]
     */
    public function getSelectedIds(): array
    {
        return $this->selectedIds;
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
            $selected[$key]->setSelectedOption($value);
        }
        return $selected;
    }

    /**
     * Set the filters
     * @param AbstractFilter[] $filters
     * @return self
     */
    public function addFilters(AbstractFilter ...$filters): self
    {
        foreach ($filters as $filter) {
            if (
                in_array('admin', $filter->getPermissions(), true)
                && !$this->user->isAdmin()
                && !in_array('user', $filter->getPermissions(), true)
            ) {
                continue;
            }

            $this->filters[$filter->getId()] = $filter;
        }
        return $this;
    }

    /**
     * Return the set metrics
     * @return AbstractFilter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function clear(): self
    {
        $this->filters = [];
        $this->selectedIds = [];
        return $this;
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
