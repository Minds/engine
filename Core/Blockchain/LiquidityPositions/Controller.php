<?php
namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Exception;
use Minds\Api\Exportable;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * LiquidityPostions Controller
 * @package Minds\Core\Blockchain\LiquidityPostions
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null
    ) {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Returns the
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function get(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        /** @var int */
        $timestamp = $request->getQueryParams()['timestamp'] ?? time() - 300;

        $summary = $this->manager
            ->setDateTs($timestamp)
            ->setUser($user)
            ->getSummary();

        return new JsonResponse(array_merge([
            'status' => 'success',
        ], $summary->export()));
    }

    /**
     * Returns a list of users providing liquidiry
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getAllUsers(ServerRequest $request): JsonResponse
    {
        $users = iterator_to_array($this->manager->getProviderUsers());

        return new JsonResponse([
            'status' => 'success',
            'users' => Exportable::_($users)
        ]);
    }
}
