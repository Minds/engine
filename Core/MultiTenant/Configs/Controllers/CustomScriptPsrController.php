<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Controllers;

use InvalidParameterException;
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

        if (mb_strlen($customScript) > 100000) {
            throw new InvalidParameterException('customScript must not be greater than 50000 characters', 400);
        }

        if (!$this->manager->upsertConfigs(customScript: $customScript)) {
            throw new ServerErrorException('Failed to update customScript', 500);
        }

        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
