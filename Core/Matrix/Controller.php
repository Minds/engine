<?php
namespace Minds\Core\Matrix;

use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Matrix Controller
 * @package Minds\Core\Matrix
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    public function __construct(Manager $manager = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getAccount(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        $matrixAccount = $this->manager->getAccountByUser($user);
        return new JsonResponse([
           'status' => 'success',
           'account' => $matrixAccount->export(),
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getRooms(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        $matrixAccount = $this->manager->getJoinedRooms($user);
        return new JsonResponse([
           'status' => 'success',
           'account' => $matrixAccount->export(),
        ]);
    }
}
