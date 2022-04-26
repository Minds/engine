<?php
namespace Minds\Core\Notifications\Push\DeviceSubscriptions;

use Minds\Entities\User;
use Minds\Core;
use Minds\Api\Exportable;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Minds\Core\Queue\Client as QueueClient;

/**
 * Registers or unregisters a push device
 * @package Minds\Core\Notifications\Push\DeviceSubscriptions
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
     * Returns count of unread notifications
     * @return JsonResponse
     *
     */
    public function registerToken(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $params = $request->getParsedBody();
        $token = $params['token'] ?? null;
        $service = $params['service'] ?? null;

        if (!$token) {
            throw new UserErrorException('token not provided in POST body');
        }

        if (!$service) {
            throw new UserErrorException('service not provided in POST body');
        }

        $deviceSubscription = new DeviceSubscription();
        $deviceSubscription->setUserGuid($user->getGuid())
            ->setToken(urldecode(($token)))
            ->setService($service);

        $this->manager->add($deviceSubscription);

        return new JsonResponse([
            'status' => 'success',
        ], 200);
    }

    /**
     * Returns count of unread notifications
     * @return JsonResponse
     *
     */
    public function deleteToken(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $token = $request->getAttribute('parameters')['token'] ?? null;

        if (!$token) {
            throw new UserErrorException(':token not supplied in DELETE uri');
        }

        $deviceSubscription = new DeviceSubscription();
        $deviceSubscription->setUserGuid($user->getGuid())
            ->setToken($token);

        $this->manager->delete($deviceSubscription);

        return new JsonResponse([
            'status' => 'success',
        ], 200);
    }
}
