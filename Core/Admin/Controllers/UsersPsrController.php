<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Controllers;

use Minds\Core\Admin\Services\UsersService;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class UsersPsrController
{
    public function __construct(
        private readonly UsersService $usersService,
    ) {
    }

    /**
     * Returns a list of users and their emails
     */
    public function getUsers(ServerRequest $request): JsonResponse
    {
        $limit = (int) $request->getQueryParams()['limit'] ?? 12;
        $offset = (int) $request->getQueryParams()['offset'] ?? 0;

        $users = $this->usersService->listUsers($limit, $offset);

        return new JsonResponse([
            'users' => $users,
        ]);
    }
}
