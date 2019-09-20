<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Router;

use Minds\Core\Router\Middleware;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Manager
{
    /** @var Middleware\RouterMiddleware[] */
    protected $middleware;

    /**
     * Manager constructor.
     * @param Middleware\RouterMiddleware[] $middleware
     */
    public function __construct(
        $middleware = null
    ) {
        $this->middleware = $middleware ?: [
            new Middleware\SEOMiddleware(),
            new Middleware\ProMiddleware(), // this needs to always be the last element in this array
        ];
    }

    /**
     * @param ServerRequest $request
     * @param JsonResponse $response
     * @return bool|null
     */
    public function handle(ServerRequest &$request, JsonResponse &$response): ?bool
    {
        $result = null;

        foreach ($this->middleware as $middleware) {
            $result = $middleware->onRequest($request, $response);

            if ($result === false) {
                break;
            }
        }

        return $result;
    }
}
