<?php
namespace Minds\Core\Matrix;

use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Matrix Controller
 * @package Minds\Core\Matrix
 */
class WellKnownController
{
    /** @var MatrixConfig */
    protected $matrixConfig;

    public function __construct(MatrixConfig $matrixConfig = null)
    {
        $this->matrixConfig = $matrixConfig ?? new MatrixConfig();
    }
    /**
     * Returns the .well-known/matrix/service links
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getServer(ServerRequest $request): JsonResponse
    {
        return new JsonResponse([
            "m.server" => "{$this->matrixConfig->getHomeserverApiDomain()}:443"
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
                "base_url" => "https://{$this->matrixConfig->getHomeserverApiDomain()}"
            ],
            "m.identity_server" => [
                "base_url" => "https://vector.im"
            ]
        ]);
    }
}
