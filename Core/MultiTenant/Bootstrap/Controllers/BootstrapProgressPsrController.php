<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Controllers;

use Minds\Core\MultiTenant\Bootstrap\Services\BootstrapProgressService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Get the bootstrap progress for a given site.
 */
class BootstrapProgressPsrController
{
    public function __construct(
        private BootstrapProgressService $bootstrapProgressService
    ) {
    }

    /**
     * Get the bootstrap progress.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getProgress(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(
            $this->bootstrapProgressService->getProgress()
        );
    }
}
