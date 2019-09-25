<?php

namespace Minds\Core\Router\Middleware;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\SEO;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class SEOMiddleware implements RouterMiddleware
{
    /** @var Config */
    protected $config;

    /**
     * SEOMiddleware constructor.
     * @param Config $config
     */
    public function __construct(
        $config = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param ServerRequest $request
     * @param JsonResponse $response
     * @return bool|null
     */
    public function onRequest(ServerRequest $request, JsonResponse &$response): ?bool
    {
        new SEO\Defaults($this->config);
        return null;
    }
}
