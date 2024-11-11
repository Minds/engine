<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Controllers;

use Minds\Core\MultiTenant\Configs\Manager as TenantConfigsManager;
use Minds\Exceptions\ServerErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Controller to update a tenants custom script.
 */
class CustomScriptPsrController
{
    public function __construct(
        private readonly TenantConfigsManager $manager,
    ) {
    }

    /**
     * Update the custom script.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $customScript = $request->getParsedBody()['customScript'] ?? null;

        if (!$this->manager->upsertConfigs(customScript: $customScript)) {
            throw new ServerErrorException('Failed to update customScript', 500);
        }

        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
