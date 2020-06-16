<?php declare(strict_types=1);
/**
 * RegistryEntry
 * @author edgebal
 */

namespace Minds\Core\Router;

use Exception;
use Minds\Traits\MagicAttributes;

/**
 * Class RegistryEntry
 * @package Minds\Core\Router
 * @method string getRoute()
 * @method mixed getBinding()
 * @method RegistryEntry setBinding(mixed $binding)
 * @method string[] getMiddleware[]
 * @method RegistryEntry setMiddleware(string[] $middleware)
 */
class RegistryEntry
{
    use MagicAttributes;

    /** @var string */
    protected $route;

    /** @var mixed */
    protected $binding;

    /** @var string[] */
    protected $middleware;

    /**
     * @param string $route
     * @return RegistryEntry
     */
    public function setRoute(string $route): RegistryEntry
    {
        $this->route = trim($route, '/');
        return $this;
    }

    /**
     * @return string
     */
    public function getWildcardRoute(): string
    {
        return preg_replace('#/:[^/]+#', '/*', $this->route);
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        if (!$this->route) {
            return -1;
        }
        
        return substr_count($this->route, '/');
    }

    /**
     * @return int
     */
    public function getSpecificity(): int
    {
        if (!$this->route) {
            return 1;
        }

        $fragments = explode('/', $this->getWildcardRoute());
        $count = count($fragments);
        $specificity = 0;

        for ($i = 0; $i < $count; $i++) {
            if ($fragments[$i] !== '*') {
                $specificity += 2 ** ($count - 1 - $i);
            }
        }

        return $specificity;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function matches(string $route): bool
    {
        $route = trim($route, '/');
        $pattern = sprintf("#^%s$#i", strtr(preg_quote($this->getWildcardRoute(), '#'), ['\*' => '[^/]+']));
        return (bool) preg_match($pattern, $route);
    }

    /**
     * @param string $route
     * @return array
     */
    public function extract(string $route): array
    {
        $route = trim($route, '/');
        $pattern = sprintf(
            '#^%s$#i',
            preg_replace_callback('#/\\\:([^/]+)#', [$this, '_regexNamedCapture'], preg_quote($this->route, '#'))
        );

        $matches = [];
        preg_match($pattern, $route, $matches);

        $parameters = [];

        foreach ($matches as $key => $value) {
            if (is_numeric($key)) {
                continue;
            }

            $parameters[$key] = rawurldecode($value);
        }

        return $parameters;
    }

    /**
     * @param array $matches
     * @return string
     * @throws Exception
     */
    protected function _regexNamedCapture(array $matches): string
    {
        $name = $matches[1] ?? '_';

        if (is_numeric($name) || !ctype_alnum($name)) {
            throw new Exception('Invalid route parameter name');
        }

        return sprintf('/(?<%s>[^/]+)', $name);
    }
}
