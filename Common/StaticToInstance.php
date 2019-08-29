<?php
/**
 * Converts a static class to use instances
 */
namespace Minds\Common;

class StaticToInstance
{
    /** @var $class */
    private $class;

    public function __construct($class)
    {
        $this->setClass($class);
    }

    /**
     * Set the class in question
     * @return StripeStaticToOO
     */
    public function setClass($class)
    {
        $this->class = new \ReflectionClass($class);
        return clone $this;
    }

    /**
     * Call the static functions as OO style
     * @param string $method
     * @param array $arguments
     * @return midex
     */
    public function __call($method, $arguments)
    {
        $instance = $this->class->newInstanceWithoutConstructor();
        return $instance::$method(...$arguments);
    }
}
