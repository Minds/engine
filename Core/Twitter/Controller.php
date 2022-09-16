<?php

declare(strict_types=1);

namespace Minds\Core\Twitter;

use Minds\Core\Di\Di;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Twitter\Manager');
    }

    public function requestTwitterOAuthToken(ServerRequestInterface $request): JsonResponse
    {
        $url = $this->manager->getRequestOAuthTokenUrl();

        return new JsonResponse(['authorization_url' => $url]);
    }

    public function storeTwitterOAuthToken(ServerRequestInterface $request): JsonResponse
    {
        print_r($request->getQueryParams());
        echo "\n\n";
        print_r($request->getParsedBody());

        return new JsonResponse([]);
    }
}
