<?php
namespace Minds\Core\Matrix;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Core\Features;
use Exception;
use Minds\Api\Exportable;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Matrix Controller
 * @package Minds\Core\Matrix
 */
class WellKnownController
{
    /**
     * Returns the .well-known/matrix/service links
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getServer(ServerRequest $request): JsonResponse
    {
        return new JsonResponse([
            "m.server" => "minds-com.ems.host:443"
        ]);
    }

    /**
     * Returns the .well-known/matrix/client links
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getClient(ServerRequest $request): JsonResponse
    {
        return new JsonResponse([
            "m.homeserver" => [
                "base_url" => "https://minds-com.ems.host"
            ],
            "m.identity_server" => [
                "base_url" => "https://vector.im"
            ]
        ]);
    }
}
