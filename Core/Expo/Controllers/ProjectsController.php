<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Controllers;

use Minds\Core\Expo\Services\ProjectsService;
use Minds\Exceptions\ServerErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Expo Projects Controller.
 */
class ProjectsController
{
    public function __construct(
        private ProjectsService $projectsService,
    ) {
    }

    /**
     * Create a new project in Expo for the tenant.
     * @param ServerRequest $request - The request.
     * @return JsonResponse - The response.
     */
    public function newProject(ServerRequest $request): JsonResponse
    {
        $requestBody = $request->getParsedBody();
        $displayName = $requestBody['display_name'];
        $slug = $requestBody['slug'];
        $privacy = $requestBody['privacy'];

        $success = $this->projectsService->newProject(
            displayName: $displayName,
            slug: $slug,
            privacy: $privacy
        );
        
        if (!$success) {
            throw new ServerErrorException('An error occurred when creating the project');
        }

        return new JsonResponse([]);
    }
}
