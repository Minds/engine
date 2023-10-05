<?php
namespace Minds\Entities;

use Minds\Core;
use Minds\Core\Data;
use Minds\Helpers;
use Minds\Traits;

/**
* Normalized Entity
*/
class NormalizedEntity
{
    use Traits\Entity;

    protected $guid;
    protected $indexes = [];
    protected $exportableDefaults = [];

    /** @var string $featured_id */
    protected $featured_id;

    /** @var int $featured */
    protected $featured;

    /** @var string $type */
    protected $type;

    /** @var string $subtype */
    protected $subtype;

    /**
    * Load entity data from an array
    * @param  $array
    * @return $this
    */
    public function loadFromArray($array)
    {
        foreach ($array as $key => $value) {
            if (Helpers\Validation::isJson($value)) { //json_decode should handle this, not sure it's needed
                $value = json_decode($value, true);
            }

            $method = Helpers\Entities::buildSetter($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            } elseif (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
    * Gets `guid`
    * @return mixed
    */
    public function getGuid(): string
    {
        if (!$this->guid) {
            $this->guid = Core\Guid::build();
        }
        return (string) $this->guid;
    }
    /**
     * Magic method for getter and setters.
     */
    public function __call($name, array $args = [])
    {
        if (strpos($name, 'set', 0) === 0) {
            $attribute = str_replace('set', '', $name);
            $attribute = lcfirst($attribute);
            $this->$attribute = $args[0];
            return $this;
        }
        if (strpos($name, 'get', 0) === 0) {
            $attribute = str_replace('get', '', $name);
            $attribute = lcfirst($attribute);
            return $this->$attribute;
        }
        return $this;
    }

    /**
     * Export the entity onto an array
     * @param  array $keys
     * @return array
     */
    public function export(array $keys = [])
    {
        $keys = array_merge($this->exportableDefaults, $keys);
        $export = [];
        foreach ($keys as $key) {
            $method = Helpers\Entities::buildGetter($key);
            if (method_exists($this, $method)) {
                $export[$key] = $this->$method();
            } elseif (property_exists($this, $key)) {
                $export[$key] = $this->$key;
            }

            if (is_object($export[$key]) && method_exists($export[$key], 'export')) {
                $export[$key] = $export[$key]->export();
            }
        }
        $export = Helpers\Export::sanitize($export);
        return $export;
    }

    public function canEdit()
    {
        return Core\Security\ACL::_()->write($this);
    }

}
