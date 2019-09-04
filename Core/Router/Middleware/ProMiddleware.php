<?php
/**
 * ProMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware;

use Minds\Core\Di\Di;
use Minds\Core\Pro\Domain;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Minds\Core\Pro\SEO;

class ProMiddleware implements RouterMiddleware
{
    /** @var Domain */
    protected $proDomain;

    /**
     * ProMiddleware constructor.
     * @param Domain $proDomain
     */
    public function __construct(
        $proDomain = null
    )
    {
        $this->proDomain = $proDomain ?: Di::_()->get('Pro\Domain');
    }

    /**
     * @param ServerRequest $request
     * @param JsonResponse $response
     * @return false|null|void
     */
    public function onRequest(ServerRequest $request, JsonResponse &$response)
    {
        $origin = $request->getServerParams()['HTTP_ORIGIN'];
        $host = parse_url($origin, PHP_URL_HOST);
//        var_dump('server');
//        var_dump($_SERVER);
//        var_dump('origin');
//        var_dump($origin);
//        var_dump('host');
//        var_dump($host);

        if (!$host) {
            return;
        }

        $settings = $this->proDomain->lookup($host);
//        var_dump('settings');
//        var_dump($settings);

        if (!$settings) {
            return;
        }

        (new SEO())->setup($settings);

        header(sprintf("Access-Control-Allow-Origin: %s", $origin));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Accept,Authorization,Cache-Control,Content-Type,DNT,If-Modified-Since,Keep-Alive,Origin,User-Agent,X-Mx-ReqToken,X-Requested-With,X-No-Cache,x-xsrf-token,x-minds-origin');

        if ($request->getMethod() === 'OPTIONS') {
            return false;
        }
    }
}
