<?php
namespace Minds\Core\Feeds\TwitterSync;

use Minds\Entities\User;
use Minds\Core\EntitiesBuilder;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Feeds Controller
 * @package Minds\Core\Feeds
 */
class Controller
{
    public function __construct(protected Manager $manager)
    {
    }

    public function getConnectedAccount(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $account = $this->manager->getConnectedAccountByUser($user);

        return new JsonResponse(array_merge([
           'status' => 'success',
        ], $account->export()));
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function connectAccount(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $twitterUsername = ltrim($request->getParsedBody()['username'], '@');

        $this->manager->connectAccount(user: $user, twitterUsername: $twitterUsername, verify: true);

        return new JsonResponse([
           'status' => 'success',
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function disconnectAccount(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $this->manager->disconnectAccount(user: $user);

        return new JsonResponse([
           'status' => 'success',
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function updateAccount(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $account = $this->manager->getConnectedAccountByUser($user);

        if (!$account) {
            throw new NotFoundException();
        }

        $discoverable = boolval($request->getParsedBody()['discoverable'] ?? true);

        $account->setDiscoverable($discoverable);

        $this->manager->updateAccount($account);

        return new JsonResponse([
           'status' => 'success',
        ]);
    }
}
