<?php

namespace Minds\Core\Entities\Propagator;

use Minds\Entities\Activity;

/**
 * Properties class that all PropagateProperties delegates should inherit
 * @package Minds\Core\Entities\Propagator
 */
abstract class Properties
{
    /**
     * @var array
     */
    protected $actsOnType = [];
    /**
     * @var array
     */
    protected $actsOnSubtype = [];
    /**
     * @var bool
     */
    protected $changed = false;

    /**
     * @return array
     */
    public function actsOnType(): array
    {
        return $this->actsOnType;
    }

    /**
     * @return array
     */
    public function actsOnSubType(): array
    {
        return $this->actsOnSubtype;
    }

    /**
     * @param $entity
     * @return bool
     * @throws \Exception
     */
    public function willActOnEntity($entity): bool
    {
        if (!is_array($this->actsOnType)) {
            throw new \Exception('actsOnType must be an array');
        }

        if (!is_array($this->actsOnSubtype)) {
            throw new \Exception('actsOnSubType must be an array');
        }

        if ($this->actsOnType === [] || in_array($entity->getType(), $this->actsOnType, true)) {
            return $this->actsOnSubtype === [] || in_array($entity->getSubtype(), $this->actsOnSubtype, true);
        }

        return false;
    }

    /**
     * @param $from
     * @param $to
     * @return bool
     */
    protected function valueHasChanged($from, $to): bool
    {
        $changed = $from !== $to;
        $this->changed |= $changed;
        return $changed;
    }

    /**
     * @return bool
     */
    public function changed(): bool
    {
        return $this->changed;
    }

    /**
     * @param $from
     * @param Activity $to
     * @return Activity
     */
    abstract public function toActivity($from, Activity $to): Activity;

    /**
     * @param Activity $from
     * @param $to
     * @return mixed
     */
    abstract public function fromActivity(Activity $from, $to);
}
