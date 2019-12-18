<?php declare(strict_types=1);
/**
 * Route
 * @author edgebal
 */

namespace Minds\Core\Router;

use Exception;
use Minds\Traits\MagicAttributes;

/**
 * Class Route
 * @package Minds\Core\Router
 * @method string getPrefix()
 * @method Route setPrefix(string $prefix)
 * @method string[] getMiddleware()
 * @method Route setMiddleware(string[] $prefix)
 */
class Route
{
    use MagicAttributes;

    /** @var string */
    protected $prefix = '/';

    /** @var string[] */
    protected $middleware = [];

    /** @var Registry */
    protected $registry;

    /** @var string[] */
    const ALLOWED_METHODS = ['get','post','put','delete'];

    /**
     * Route constructor.
     * @param Registry|null $registry
     */
    public function __construct(
        Registry $registry = null
    ) {
        $this->registry = $registry ?: Registry::_();
    }

    /**
     * @param string $prefix
     * @return Route
     */
    public function withPrefix(string $prefix): Route
    {
        $instance = clone($this);
        $instance->setPrefix(sprintf("/%s/%s", trim($instance->getPrefix(), '/'), trim($prefix, '/')));

        return $instance;
    }

    /**
     * @param string[] $middleware
     * @return Route
     */
    public function withMiddleware(array $middleware): Route
    {
        $instance = clone($this);
        $instance->setMiddleware(array_merge($instance->getMiddleware(), $middleware));

        return $instance;
    }

    /**
     * @param callable $fn
     * @return Route
     */
    public function do(callable $fn): Route
    {
        call_user_func($fn, $this);
        return $this;
    }

    /**
     * @param string[] $methods
     * @param string $route
     * @param $binding
     * @return bool
     * @throws Exception
     */
    public function register(array $methods, string $route, $binding): bool
    {
        if (array_diff($methods, static::ALLOWED_METHODS)) {
            throw new Exception('Invalid method');
        }

        $route = sprintf("/%s/%s", trim($this->getPrefix(), '/'), trim($route, '/'));
        $route = trim($route, '/');

        foreach ($methods as $method) {
            $this->registry->register($method, $route, $binding, $this->middleware);
        }

        return true;
    }

    /**
     * @param string $route
     * @param $binding
     * @return bool
     * @throws Exception
     */
    public function all(string $route, $binding): bool
    {
        return $this->register(static::ALLOWED_METHODS, $route, $binding);
    }

    /**
     * @param string $route
     * @param $binding
     * @return bool
     * @throws Exception
     */
    public function get(string $route, $binding): bool
    {
        return $this->register(['get'], $route, $binding);
    }

    /**
     * @param string $route
     * @param $binding
     * @return bool
     * @throws Exception
     */
    public function post(string $route, $binding): bool
    {
        return $this->register(['post'], $route, $binding);
    }

    /**
     * @param string $route
     * @param $binding
     * @return bool
     * @throws Exception
     */
    public function put(string $route, $binding): bool
    {
        return $this->register(['put'], $route, $binding);
    }

    /**
     * @param string $route
     * @param $binding
     * @return bool
     * @throws Exception
     */
    public function delete(string $route, $binding): bool
    {
        return $this->register(['delete'], $route, $binding);
    }
}
