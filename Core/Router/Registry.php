<?php declare(strict_types=1);
/**
 * Registry
 * @author edgebal
 */

namespace Minds\Core\Router;

class Registry
{
    /** @var Registry */
    protected static $instance;

    /** @var array */
    protected $registry = [];

    /**
     * @return Registry
     */
    public static function _(): Registry
    {
        if (!static::$instance) {
            static::$instance = new Registry();
        }

        return static::$instance;
    }

    /**
     * @param string $method
     * @param string $route
     * @param mixed $binding
     * @param string[] $middleware
     * @return Registry
     */
    public function register(string $method, string $route, $binding, array $middleware): Registry
    {
        $method = strtolower($method);

        if (!isset($this->registry[$method])) {
            $this->registry[$method] = [];
        }

        $registryEntry = new RegistryEntry();
        $registryEntry
            ->setRoute($route)
            ->setBinding($binding)
            ->setMiddleware($middleware);

        $this->registry[$method][] = $registryEntry;

        return $this;
    }

    /**
     * @param string $method
     * @param string $route
     * @return RegistryEntry|null
     */
    public function getBestMatch(string $method, string $route):? RegistryEntry
    {
        if (!isset($this->registry[$method]) || !$this->registry[$method]) {
            return null;
        }

        $route = trim($route, '/');

        /** @var RegistryEntry[] $sortedRegistryEntries */
        $sortedRegistryEntries = $this->registry[$method];
        usort($sortedRegistryEntries, [$this, '_registryEntrySort']);

        foreach ($sortedRegistryEntries as $registryEntry) {
            if ($registryEntry->matches($route)) {
                return $registryEntry;
            }
        }

        return null;
    }

    /**
     * @param RegistryEntry $a
     * @param RegistryEntry $b
     * @return int
     */
    protected function _registryEntrySort(RegistryEntry $a, RegistryEntry $b): int
    {
        if ($a->getDepth() !== $b->getDepth()) {
            return $b->getDepth() - $a->getDepth();
        }

        return $b->getSpecificity() - $a->getSpecificity();
    }
}
