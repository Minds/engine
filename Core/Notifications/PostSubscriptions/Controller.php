<?php

/**
 * Minds Post Notifications controller
 */

namespace Minds\Core\Notifications\PostSubscriptions;

use Minds\Api\Factory;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications\PostSubscriptions\Manager;
use Minds\Core\Session;
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

    // ojm todo
    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        $user = Session::getLoggedinUser();
        $entityGuid = $pages[0];

        $manager = (new Manager());
        $manager
            ->setEntityGuid($entityGuid)
            ->setUserGuid($user->guid);

        $saved = $manager->follow(true);

        $entity = (new EntitiesBuilder())->single($entityGuid);
        if ($saved && $entity && $entity->entity_guid) {
            $manager
                ->setEntityGuid($entity->entity_guid)
                ->follow(true);
        }

        return Factory::response([
            'done' => $saved
        ]);
    }

    // ojm todo
    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        $user = Session::getLoggedinUser();
        $entityGuid = $pages[0];

        $manager = (new Manager());
        $manager
            ->setEntityGuid($entityGuid)
            ->setUserGuid($user->guid);

        $saved = $manager->unfollow();

        $entity = (new EntitiesBuilder())->single($entityGuid);
        if ($saved && $entity && $entity->entity_guid) {
            $manager
                ->setEntityGuid($entity->entity_guid)
                ->unfollow();
        }

        return Factory::response([
            'done' => $saved
        ]);
    }
}
