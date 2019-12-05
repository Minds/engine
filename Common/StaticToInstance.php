<?php
/**
 * Converts a static class to use instances
 */

namespace Minds\Common;

use ReflectionClass;
use ReflectionException;

class StaticToInstance
{
    /** @var ReflectionClass */
    private $class;

    /**
     * StaticToInstance constructor.
     * @param $class
     * @throws ReflectionException
     */
    public function __construct($class)
    {
        $this->setClass($class);
    }

    /**
     * Set the class in question
     * @param $class
     * @return static
     * @throws ReflectionException
     */
    public function setClass($class)
    {
        $this->class = new ReflectionClass($class);
        return clone $this;
    }

    /**
     * Call the static functions as OO style
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $instance = $this->class->newInstanceWithoutConstructor();
        return $instance::$method(...$arguments);
    }
}
