<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

use Minds\Core\Analytics\Dashboards\DashboardCollectionInterface;

class TimespansCollection implements DashboardCollectionInterface
{
    /** @var TimespanAbstract[] */
    private $timespans = [];

    /** @var string */
    private $selectedId;

    /**
     * Set the current timespan we are using
     * @param string $selectedId
     * @return self
     */
    public function setSelectedId(string $selectedId): self
    {
        $this->selectedId = $selectedId;
        return $this;
    }

    /**
     * Return the selected timespan
     * @return TimespanAbstract
     */
    public function getSelected(): TimespanAbstract
    {
        return $this->timespans[$this->selectedId];
    }

    /**
     * Set the timespans
     * @param TimespanAbstract[] $timespans
     * @return self
     */
    public function addTimespans(TimespanAbstract ...$timespans): self
    {
        foreach ($timespans as $timespan) {
            $this->timespans[$timespan->getId()] = $timespan;
        }
        return $this;
    }

    /**
     * Return the set timestamps
     * @return TimestampAbstract[]
     */
    public function getTimespans(): array
    {
        return $this->timespans;
    }

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        $export = [];
        foreach ($this->timespans as $timespan) {
            $export[] = $timespan->export();
        }
        return $export;
    }
}
