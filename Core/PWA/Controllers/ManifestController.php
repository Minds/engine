<?php
declare(strict_types=1);

namespace Minds\Core\PWA\Controllers;

use Minds\Core\PWA\Services\ManifestService;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * PWA Web Manifest Controller
 */
class ManifestController
{
    public function __construct(
        private ManifestService $service
    ) {
    }

    /**
     * Gets Web Manifest from service.
     * @param ServerRequestInterface $request - request object.
     * @return JsonResponse json response with manifest.
     */
    public function getManifest(ServerRequestInterface $request): JsonResponse
    {
        return new JsonResponse($this->service->getManifest()->export());
    }
}
