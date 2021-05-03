<?php
namespace Minds\Core\Matrix;

use GuzzleHttp\Exception\ClientException;
use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
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

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(Manager $manager = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->manager = $manager ?? new Manager();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getAccount(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        $matrixAccount = $this->manager->getAccountByUser($user);

        $this->manager->syncAccount($user);

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
        $joinedRooms = $this->manager->getJoinedRooms($user);
        return new JsonResponse([
           'status' => 'success',
           'rooms' => Exportable::_($joinedRooms),
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getTotalUnread(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');

        try {
            $joinedRooms = $this->manager->getJoinedRooms($user);

            $sum = 0;

            foreach ($joinedRooms as $room) {
                $sum += $room->getUnreadCount();
            }
        } catch (ClientException $e) {
            $sum = 0;
        }

        return new JsonResponse([
           'status' => 'success',
           'total_unread' => $sum,
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function createDirectRoom(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');

        $receiverGuid = $request->getAttribute('parameters')['receiverGuid'] ?? null;

        $receiver = $this->entitiesBuilder->single($receiverGuid);

        if (!$receiver || !$receiver instanceof User) {
            throw new UserErrorException('User not found');
        }

        $newRoom = $this->manager->createDirectRoom($user, $receiver);
        return new JsonResponse([
           'status' => 'success',
           'room' => $newRoom->export(),
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getRawState(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        return  new JsonResponse([
            'status' => 'success',
            'state' => $this->manager->getState($user)]);
    }
}
