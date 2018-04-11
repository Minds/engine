<?php

namespace Minds\Api;

use Minds\Interfaces;
use Minds\Helpers;
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
    public static function build($segments)
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
                    if ($handler instanceof Interfaces\ApiAdminPam) {
                        self::adminCheck();
                    }
                    if (!$handler instanceof Interfaces\ApiIgnorePam) {
                        self::pamCheck();
                    }
                    $pages = array_splice($segments, $loop) ?: array();
                    return $handler->$method($pages);
                }
            }

            //autloaded routes
            $class_name = "\\Minds\\Controllers\api\\$route";
            if (class_exists($class_name)) {
                $handler = new $class_name();
                if ($handler instanceof Interfaces\ApiAdminPam) {
                    self::adminCheck();
                }
                if (!$handler instanceof Interfaces\ApiIgnorePam) {
                    self::pamCheck();
                }
                $pages = array_splice($segments, $loop) ?: array();
                return $handler->$method($pages);
            }
            --$loop;
        }
    }

    /**
     * Terminates an API response based on PAM policies for current user
     * @return bool|null
     */
    public static function pamCheck()
    {
        //error_log("checking pam");
        $user_pam = new \ElggPAM('user');
        $api_pam = new \ElggPAM('api');
        $user_auth_result = $user_pam->authenticate();
        if ($user_auth_result && $api_pam->authenticate() || Security\XSRF::validateRequest()) {
            return true;
        } else {
            //error_log('failed authentication:: OAUTH via API');
            ob_end_clean();
            header('Content-type: application/json');
            header("Access-Control-Allow-Origin: *");
            header('HTTP/1.1 401 Unauthorized', true, 401);
            echo json_encode([
                'error' => 'Sorry, you are not authenticated', 
                'code' => 401,
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
            header('Content-type: application/json');
            header("Access-Control-Allow-Origin: *");
            header('HTTP/1.1 401 Unauthorized', true, 401);
            echo json_encode(array('error'=>'You are not an admin', 'code'=>401));
            exit;
        }
    }

    /**
     * Terminates an API response if there's no user session
     * @return bool|null
     */
    public static function isLoggedIn()
    {
        if(Session::isLoggedIn()){
            return true;
        } else {
            ob_end_clean();
            header('Content-type: application/json');
            header("Access-Control-Allow-Origin: *");
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
    public static function response($data = array())
    {
        $data = array_merge(array(
            'status' => 'success', //should success be assumed?
        ), $data);

        ob_end_clean();

        header('Content-type: application/json');
        header("Access-Control-Allow-Origin: *");
        echo json_encode($data);
    }

    /**
     * Returns the exportable form of the entities
     * @param array $entities - an array of entities
     * @return array - an array of the entities
     * @deprecated
     */
    public static function exportable($entities, $exceptions = array(), $exportContext = false)
    {
        if(!$entities){
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
