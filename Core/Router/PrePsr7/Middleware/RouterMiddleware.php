<?php
/**
 * RouterMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\PrePsr7\Middleware;

use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

interface RouterMiddleware
{
    /**
     * @param ServerRequest $request
     * @param JsonResponse $response
     * @return bool|null
     */
    public function onRequest(ServerRequest $request, JsonResponse &$response): ?bool;
}
