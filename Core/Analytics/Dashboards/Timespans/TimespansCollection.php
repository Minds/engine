<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

use Minds\Core\Analytics\Dashboards\DashboardCollectionInterface;

class TimespansCollection implements DashboardCollectionInterface
{
    /** @var AbstractTimespan[] */
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
     * @return AbstractTimespan
     */
    public function getSelected(): AbstractTimespan
    {
        return $this->timespans[$this->selectedId];
    }

    /**
     * Set the timespans
     * @param AbstractTimespan[] $timespans
     * @return self
     */
    public function addTimespans(AbstractTimespan ...$timespans): self
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
            if ($timespan->getId() === $this->selectedId) {
                $timespan->setSelected(true);
            }
            $export[] = $timespan->export();
        }
        return $export;
    }
}
