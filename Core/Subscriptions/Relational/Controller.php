<?php
namespace Minds\Core\Subscriptions\Relational;

use Minds\Api\Exportable;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(protected ?Repository $repository = null)
    {
        $this->repository ??= new Repository();
    }

    /**
    * Returns subscriptions of subscriptions, ordered by most relevant
    * @param ServerRequest $request
    * @return JsonResponse
    */
    public function getSubscriptionsOfSubscriptions(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $loggedInUser = $request->getAttribute('_user');

        /** @var int */
        $limit = $request->getQueryParams()['limit'] ?? 3;

        /** @var int */
        $offset = $request->getQueryParams()['offset'] ?? 0;

        $users = iterator_to_array($this->repository->getSubscriptionsOfSubscriptions(
            userGuid: $loggedInUser->getGuid(),
            limit: (int) $limit,
            offset: (int) $offset,
        ));

        return new JsonResponse([
            'users' => Exportable::_($users),
        ]);
    }

    /**
     * Returns users who **I subscribe to** that also subscribe to this users
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getSubscriptionsThatSubscribeTo(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $loggedInUser = $request->getAttribute('_user');

        /** @var string */
        $subscribedToGuid = $request->getQueryParams()['guid'] ?? null;

        /** @var int */
        $limit = $request->getQueryParams()['limit'] ?? 3;

        /** @var int */
        $offset = $request->getQueryParams()['offset'] ?? 0;

        if (!$subscribedToGuid) {
            throw new UserErrorException("You must provide ?guid parameter");
        }

        $count =  $this->repository->getSubscriptionsThatSubscribeToCount(
            userGuid: $loggedInUser->getGuid(),
            subscribedToGuid: $subscribedToGuid
        );

        $users = iterator_to_array($this->repository->getSubscriptionsThatSubscribeTo(
            userGuid: $loggedInUser->getGuid(),
            subscribedToGuid: $subscribedToGuid,
            limit: (int) $limit,
            offset: (int) $offset,
        ));

        return new JsonResponse([
            'count' => $count,
            'users' => Exportable::_($users),
        ]);
    }
}
