<?php

namespace Minds\Api;

use Minds\Interfaces;
use Minds\Core\Security;
use Minds\Core\Session;

/**
 * API Factory
 */
class Factory
{
    /**
     * Executes an Api\Controller method for the passed $segments
     * based on the current HTTP request method,
     * or null if the class is not found.
     * @param string $segments - String representing a route
     * @return mixed|null
     */
    public static function build($segments, $request, $response)
    {
        //try {
        //    Helpers\RequestMetrics::increment('api');
        //} catch (\Exception $e) {
        //}

        $method = strtolower($_SERVER['REQUEST_METHOD']);

        $route = implode('\\', $segments);
        $loop = count($segments);
        while ($loop >= 0) {
            $offset = $loop -1;

            if ($loop < count($segments)) {
                $slug_length = strlen($segments[$offset+1].'\\');
                $route_length = strlen($route);
                $route = substr($route, 0, $route_length-$slug_length);
            }

            //Literal routes
            $actual = str_replace('\\', '/', $route);
            if (isset(Routes::$routes[$actual])) {
                $class_name = Routes::$routes[$actual];

                if (class_exists($class_name)) {
                    $handler = new $class_name();

                    if (property_exists($handler, 'request')) {
                        $handler->request = $request;
                    }

                    if ($handler instanceof Interfaces\ApiAdminPam) {
                        self::adminCheck();
                    }

                    if (!$handler instanceof Interfaces\ApiIgnorePam) {
                        self::pamCheck($request, $response);
                    }

                    $pages = array_splice($segments, $loop) ?: [];

                    return $handler->$method($pages);
                }
            }

            //autloaded routes
            $class_name = "\\Minds\\Controllers\api\\$route";

            if (class_exists($class_name)) {
                $handler = new $class_name();

                if (property_exists($handler, 'request')) {
                    $handler->request = $request;
                }

                if ($handler instanceof Interfaces\ApiAdminPam) {
                    self::adminCheck();
                }

                if (!$handler instanceof Interfaces\ApiIgnorePam) {
                    self::pamCheck($request, $response);
                }

                $pages = array_splice($segments, $loop) ?: [];

                return $handler->$method($pages);
            }

            --$loop;
        }
    }

    /**
     * Terminates an API response based on PAM policies for current user
     * @return bool|null
     */
    public static function pamCheck($request, $response)
    {
        if (
            $request->getAttribute('oauth_user_id') ||
            Security\XSRF::validateRequest()
        ) {
            return true;
        } else {
            //error_log('failed authentication:: OAUTH via API');
            ob_end_clean();

            static::setCORSHeader();

            $code = !Security\XSRF::validateRequest() ? 403 : 401;

            if (isset($_SERVER['HTTP_APP_VERSION'])) {
                $code = 401; // Mobile requires 401 errors
            }

            header('Content-type: application/json');
            http_response_code($code);
            echo json_encode([
                'error' => 'Sorry, you are not authenticated',
                'code' => $code,
                'loggedin' => false
                ]);
            exit;
        }
    }

    /**
     * Terminates an API response if the current user is not an administrator
     * @return bool|null
     */
    private static function adminCheck()
    {
        if (Session::isLoggedIn() && Session::getLoggedinUser()->isAdmin()) {
            return true;
        } else {
            error_log('security: unauthorized access to admin api');
            ob_end_clean();

            static::setCORSHeader();

            header('Content-type: application/json');
            header('HTTP/1.1 401 Unauthorized', true, 401);
            echo json_encode(['error'=>'You are not an admin', 'code'=>401]);
            exit;
        }
    }

    /**
     * Terminates an API response if there's no user session
     * @return bool|null
     */
    public static function isLoggedIn()
    {
        if (Session::isLoggedIn()) {
            return true;
        } else {
            ob_end_clean();

            static::setCORSHeader();

            header('Content-type: application/json');
            header('HTTP/1.1 401 Unauthorized', true, 401);
            echo json_encode([
              'status' => 'error',
              'message' => 'You are not not logged in',
              'code' => 401,
              'loggedin' => false
            ]);
            exit;
        }
    }

    /**
     * Builds an API response
     * @param array $data
     */
    public static function response($data = [])
    {
        $data = array_merge([
            'status' => 'success', //should success be assumed?
        ], $data);

        if (ob_get_level() > 1) {
            // New PSR-7 Router has an OB started all the time
            ob_end_clean();
        }

        static::setCORSHeader();

        header('Content-type: application/json');
        echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Sets the CORS header, if not already set
     */
    public static function setCORSHeader(): void
    {
        $wasSet = count(array_filter(headers_list(), function ($header) {
            return stripos($header, 'Access-Control-Allow-Origin:') === 0;
        })) > 0;

        if (!$wasSet) {
            header("Access-Control-Allow-Origin: *");
        }
    }

    /**
     * Returns the exportable form of the entities
     * @param array $entities - an array of entities
     * @return array - an array of the entities
     * @deprecated
     */
    public static function exportable($entities, $exceptions = [], $exportContext = false)
    {
        if (!$entities) {
            return [];
        }
        foreach ($entities as $k => $entity) {
            if ($exportContext && method_exists($entity, 'setExportContext')) {
                $entity->setExportContext($exportContext);
            }

            $entities[$k] = $entity->export();
            $entities[$k]['guid'] = (string) $entities[$k]['guid']; //javascript doesn't like long numbers..
            if (isset($entities[$k]['ownerObj']['guid'])) {
                $entities[$k]['ownerObj']['guid'] = (string) $entity->ownerObj['guid'];
            }
            foreach ($exceptions as $exception) {
                $entities[$k][$exception] = $entity->$exception;
            }
        }
        return $entities;
    }
}
