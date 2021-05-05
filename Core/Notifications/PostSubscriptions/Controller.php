<?php

/**
 * Post subscriptions controller
 */

namespace Minds\Core\Notifications\PostSubscriptions;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications\PostSubscriptions\Manager;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

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
     * Gets user's post subscription
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function get(ServerRequest $request): JsonResponse
    {
        $entityGuid = $request->getQueryParams()['entity_guid'];

        if (!$entityGuid) {
            throw new UserErrorException("Entity guid required");
        }

        /** @var User */
        $user = $request->getAttribute('_user');

        $this->manager
            ->setEntityGuid($entityGuid)
            ->setUserGuid($user->guid);

        $postSubscription = $this->manager->get();

        return new JsonResponse([
            'status' => 'success',
            'postSubscription' => $postSubscription->export()
        ]);
    }

    /**
     * Updates a user's post subscription
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function put(ServerRequest $request): JsonResponse
    {
        $body = $request->getParsedBody();

        $entityGuid = $body['entity_guid'];

        if (!$entityGuid) {
            throw new UserErrorException("Entity guid required");
        }

        /** @var User */
        $user = $request->getAttribute('_user');

        $this->manager
            ->setEntityGuid($entityGuid)
            ->setUserGuid($user->guid);

        $saved = $this->manager->follow(true);

        $entity = (new EntitiesBuilder())->single($entityGuid);
        if ($saved && $entity && $entity->entity_guid) {
            $this->manager
                ->setEntityGuid($entity->entity_guid)
                ->follow(true);
        }

        return new JsonResponse([
            'status' => 'success',
            'done' => $saved
        ]);
    }

    /**
     * Deletes a user's post subscription
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function delete(ServerRequest $request): JsonResponse
    {
        $body = $request->getParsedBody();

        $entityGuid = $body['entity_guid'];

        if (!$entityGuid) {
            throw new UserErrorException("Entity guid required");
        }

        /** @var User */
        $user = $request->getAttribute('_user');

        $this->manager
            ->setEntityGuid($entityGuid)
            ->setUserGuid($user->guid);

        $saved = $this->manager->unfollow();

        $entity = (new EntitiesBuilder())->single($entityGuid);
        if ($saved && $entity && $entity->entity_guid) {
            $this->manager
                ->setEntityGuid($entity->entity_guid)
                ->unfollow();
        }

        return new JsonResponse([
            'status' => 'success',
            'done' => $saved
        ]);
    }
}
