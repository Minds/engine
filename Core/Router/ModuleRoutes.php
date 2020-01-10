<?php declare(strict_types=1);
/**
 * ModuleRoutes
 * @author edgebal
 */

namespace Minds\Core\Router;

abstract class ModuleRoutes
{
    /** @var Route */
    protected $route;

    /**
     * ModuleRoutes constructor.
     * @param Route $route
     */
    public function __construct(
        $route = null
    ) {
        $this->route = $route ?: new Route();
    }

    /**
     * Registers all module routes
     */
    abstract public function register(): void;
}
