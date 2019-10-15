<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Analytics\Dashboards\Timespans\TimespansCollection;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Traits\MagicAttributes;

/**
 * @method AbstractMetric setTimespansCollection(TimespansCollection $timespansCollection)
 * @method AbstractMetric setFiltersCollection(FiltersCollection $filtersCollection)
 * @method string getId()
 * @method string getLabel()
 * @method MetricSummary getSummary()
 */
abstract class AbstractMetric
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var string */
    protected $label;

    /** @var string */
    protected $description;

    /** @var string */
    protected $unit = 'number';

    /** @var string[] */
    protected $permissions;

    /** @var MetricSummary */
    protected $summary;

    /** @var VisualisationInterface */
    protected $visualisation;

    /** @var TimespansCollection */
    protected $timespansCollection;

    /** @var FiltersCollection */
    protected $filtersCollection;

    /**
     * Return the usd guid for metrics
     * @return string
     */
    protected function getUserGuid(): ?string
    {
        $filters = $this->filtersCollection->getSelected();
        $channelFilter = $filters['channel'];

        if (!$channelFilter) {
            if (!Session::getLoggedInUserGuid()) {
                throw new \Exception("You must be loggedin");
            }
            if (Session::isAdmin()) {
                return "";
            }
            return Session::getLoggedInUserGuid();
        }

        if ($channelFilter->getSelectedOption() === 'all') {
            if (Session::isAdmin()) {
                return "";
            }
            $channelFilter->setSelectedOption('self');
        }

        if ($channelFilter->getSelectedOption() === 'self') {
            return Session::getLoggedInUserGuid();
        }

        // TODO: check permissions first
        return $channelFilter->getSelectedOption();
    }

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'id' => (string) $this->id,
            'label' => (string) $this->label,
            'description' => (string) $this->description,
            'unit' => (string) $this->unit,
            'permissions' => (array) $this->permissions,
            'summary' => $this->summary ? (array) $this->summary->export() : null,
            'visualisation' => $this->visualisation ? (array) $this->visualisation->export() : null,
        ];
    }
}
