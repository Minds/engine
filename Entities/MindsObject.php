<?php
namespace Minds\Entities;

use Minds\Interfaces\Flaggable;
use Minds\Core;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Core\Wire\Paywall\PaywallEntityTrait;

/**
 * Object Entity
 * @todo Do not inherit from ElggObject
 * @property int $hidden
 * @property string $super_subtype
 * @property int $wire_threshold
 * @property int $deleted
 * @property int $paywall
 */
class MindsObject extends \ElggObject implements Flaggable, PaywallEntityInterface
{
    use PaywallEntityTrait;

    /** @var bool */
    protected $dirtyIndexes;

    /**
     * Initialize entity attributes
     * @return null
     */
    protected function initializeAttributes()
    {
        parent::initializeAttributes();

        $this->attributes['flags'] = [];
        $this->attributes['wire_threshold'] = 0;
        $this->attributes['rating'] = 2;
    }

    /**
     * Returns an array of which Entity attributes are exportable
     * @return array
     */
    public function getExportableValues()
    {
        return array_merge(parent::getExportableValues(), [
            'flags',
            'wire_threshold'
        ]);
    }

    /**
     * Gets a flag value. Null if not found.
     * @param  string $flag
     * @return mixed|null
     */
    public function getFlag($flag)
    {
        if (!isset($this->attributes['flags']) || !$this->attributes['flags']) {
            return false;
        }

        return isset($this->attributes['flags'][$flag]) && !!$this->attributes['flags'][$flag];
    }

    /**
     * Sets a flag value.
     * @param  string $flag
     * @param  mixed  $value
     * @return $this
     */
    public function setFlag($flag, $value)
    {
        if (!isset($this->attributes['flags'])) {
            $this->attributes['flags'] = [];
        }

        $this->attributes['flags'][$flag] = !!$value;

        if ($flag == 'deleted') {
            $this->dirtyIndexes = true;
        }

        return $this;
    }

    /**
     * Gets the `deleted` flag.
     *
     * @return bool
     */
    public function getDeleted(): ?bool
    {
        return (bool) $this->deleted || $this->getFlag('deleted');
    }

    /**
     * Returns the sum of every wire that's been made to this entity
     */
    public function getWireTotals()
    {
        $totals = [];
        // $totals['bitcoin'] = \Minds\Core\Wire\Counter::getSumByEntity($this->guid, 'bitcoin');
        return $totals;
    }
}
