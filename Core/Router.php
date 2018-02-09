<?php
namespace Minds\Core;

use Minds\Helpers;
use Minds\Controllers;

/**
 * Minds Core Router
 */
class Router
{
    // these are core pages, other pages are registered by plugins
    public static $routes = array(
      '/archive/thumbnail' => "Minds\\Controllers\\fs\\v1\\thumbnail",

      '/api/v1/archive' => "Minds\\Controllers\\api\\v1\\media",
      '/api/v1/archive/albums' => "Minds\\Controllers\\api\\v1\\media\\albums",
      '/api/v1/archive/thumbnails' => "Minds\\Controllers\\api\\v1\\media\\thumbnails",

      '/api/v1/thumbs' => Controllers\api\v1\votes::class,

      '/icon' => "Minds\\Controllers\\icon",
      '//icon' => "Minds\\Controllers\\icon",
      '/api' => "Minds\\Controllers\\api\\api",
      '/fs' => "Minds\\Controllers\\fs\\fs",
      '/thumbProxy' => "Minds\\Controllers\\thumbProxy",
      '/wall' => "Minds\\Controllers\\Legacy\\wall",
      '/not-supported' => "Minds\Controllers\\notSupported",
        //  "/app" => "minds\\pages\\app",
      '/emails/unsubscribe' => "Minds\\Controllers\\emails\\unsubscribe",
      '/sitemap' => "Minds\\Controllers\\sitemap",

      '/api/v1/gatherings' => "\\Minds\\Controllers\\api\\v1\\messenger\\conversations",
      '/api/v1/conversations' => "\\Minds\\Controllers\\api\\v1\\messenger\\conversations",
      '/api/v1/keys' => "Minds\\Controllers\\api\\v1\\messenger\\keys",

      '/api/v2/conversations' => "\\Minds\\Controllers\\api\\v2\\messenger\\conversations",
      '/api/v2/conversations/search' => "\\Minds\\Controllers\\api\\v2\\messenger\\search",
      '/api/v2/keys' => "\\Minds\\Controllers\\api\\v2\\messenger\\keys",

    );

    /**
     * Route the pages
     * (fallback to elgg page handler if we fail)
     * @param  string $uri
     * @param  string $method
     * @return null
     */
    public function route($uri = null, $method = null)
    {
        if ((!$uri) && (isset($_SERVER['REDIRECT_ORIG_URI']))) {
            $uri = strtok($_SERVER['REDIRECT_ORIG_URI'], '?');
        }

        if (!$uri) {
            $uri = strtok($_SERVER["REQUEST_URI"], '?');
        }

        $this->detectContentType();

        $route = rtrim($uri, '/');
        $segments = explode('/', $route);
        $method = $method ? $method : strtolower($_SERVER['REQUEST_METHOD']);

        if ($method == 'post') {
            $this->postDataFix();
        }

        if (Session::isLoggedin()) {
            Helpers\Analytics::increment("active");
        }

        if (isset($_GET['__e_ct_guid'])) {
            Helpers\Analytics::increment("active", time(), $_GET['__e_ct_guid']);
            Helpers\Analytics::increment("email:clicks", time(), $_GET['__e_ct_guid']);
            Helpers\Campaigns\EmailRewards::reward($_GET['campaign'], $_GET['__e_ct_guid']);
        }

        $loop = count($segments);
        while ($loop >= 0) {
            $offset = $loop -1;
            if ($loop < count($segments)) {
                $slug_length = strlen($segments[$offset+1].'/');
                $route_length = strlen($route);
                $route = substr($route, 0, $route_length-$slug_length);
            }

            if (isset(self::$routes[$route])) {
                $handler = new self::$routes[$route]();
                $pages = array_splice($segments, $loop) ?: array();
                if (method_exists($handler, $method)) {
                    return $handler->$method($pages);
                } else {
                    exit;
                }
            }
            --$loop;
        }

        if (!$this->legacyRoute($uri)) {
            include(dirname(dirname(dirname((__FILE__)))) . '/front/public/index.php');
        }
    }

    /**
     * Legacy Router fallback
     * @param  string   $uri
     * @return boolean
     */
    public function legacyRoute($uri)
    {
        $path = explode('/', substr($uri, 1));
        $handler = array_shift($path);
        $page = implode('/', $path);

        new page(false); //just to load init etc

        if (!\page_handler($handler, $page)) {
            return false;
        }

        return true;
    }

    /**
     * Detects request content type and apply the corresponding polyfills
     * @return null
     */
    public function detectContentType()
    {
        if (isset($_SERVER["CONTENT_TYPE"]) && $_SERVER["CONTENT_TYPE"] == 'application/json') {
            \elgg_set_viewtype('json');
            if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
                $this->postDataFix();
            }
        }
    }

    /**
     * Populates $_POST and $_REQUEST with request's JSON payload
     * @return null
     */
    public function postDataFix()
    {
        $postdata = file_get_contents("php://input");
        $request = json_decode($postdata, true);
        if ($request) {
            foreach ($request as $k => $v) {
                $_POST[$k] = $v;
                $_REQUEST[$k] = $v;
            }
        }
    }

    /**
     * Register routes
     *
     * @param  array $routes - an array of routes to handlers
     * @return array         - the array of all your routes
     */
    public static function registerRoutes($routes = array())
    {
        return self::$routes = array_merge(self::$routes, $routes);
    }
}
