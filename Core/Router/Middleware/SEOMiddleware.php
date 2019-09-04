<?php

namespace Minds\Core\Router\Middleware;

use Minds\Core\Di\Di;
use Minds\Core\SEO;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class SEOMiddleware implements RouterMiddleware
{
    public function onRequest(ServerRequest $request, JsonResponse &$response)
    {
        new SEO\Defaults(Di::_()->get('Config'));
    }

}
