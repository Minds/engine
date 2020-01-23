<?php
/**
 * SEO Manager
 */

namespace Minds\Core\SEO;

use Minds\Core\Events\Dispatcher;

class Manager
{
    public static $routes = [];
    public static $defaults = [
        'title' => '',
    ];

    public static function reset()
    {
        self::$routes = [];
        self::$defaults = ['title' => ''];
    }

    /**
     * Add a callback to provide metadata
     * @param string $route
     * @param callable $callback
     */
    public static function add($route, $callback)
    {
        self::$routes[$route] = $callback;
    }

    /**
     * Set default metadata
     * @param array $meta
     * @return void
     */
    public static function setDefaults($meta)
    {
        self::$defaults = array_merge(self::$defaults, $meta);
    }

    /**
     * Return metadata for given route
     * @param string $route (optional)
     * @return array
     */
    public static function get($route = null)
    {
        if (!$route) { //detect route
            $route = rtrim(strtok($_SERVER["REQUEST_URI"], '?'), '/');
        }

        $slugs = [];
        $params = null;
        $meta = [];
        $originalRoute = $route;

        while ($route) {
            $event = Dispatcher::trigger('seo:route', $route, [
                'route' => $route,
                'slugs' => array_reverse($slugs),
            ], false);

            if ($event !== false) {
                $meta = $event;
                break;
            }

            if (isset(self::$routes[$route])) {
                $meta = call_user_func_array(self::$routes[$route], [array_reverse($slugs), $params, $originalRoute]) ?: [];
                break;
            } else {
                if (!$params) {
                    $list = explode(';', $route);
                    $route = array_shift($list); // remove url part

                    $params = [];
                    foreach($list as $v) {
                        $v = explode('=', $v);
                        $params[$v[0]] = $v[1] ?? '';
                    }
                }
                $slugs[] = substr($route, strrpos($route, '/') + 1);
                if (strrpos($route, '/') === 0) {
                    $route = '/';
                } else {
                    $route = substr($route, 0, strrpos($route, '/'));
                }
            }
        }

        return array_merge(self::$defaults, $meta);
    }
}
