<?php
namespace Minds\Core\Notifications;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Exception;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\Manager;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Notifications Controller
 * @package Minds\Core\Notifications
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
     * @throws Exception
     *
     */
    public function getCount(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $count = $this->manager
            ->setUser($user)
            ->getCount();

        return new JsonResponse([
            'status' => 'success',
            'count' => $count,
        ]);
    }

    /**
     * Returns a user's push notification settings
     * @return JsonResponse
     * @throws Exception
     *
     */
    public function getSettings(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $toggles = $this->manager->getSettings($user->getGuid());

        return new JsonResponse([
            'status' => 'success',
            'toggles' => $toggles,
        ]);
    }

    /**
    * Returns single notification
    * @return JsonResponse
    * @throws Exception
    *
    */
    public function getSingle(ServerRequest $request): JsonResponse
    {
        $guid = $request->getAttribute('guid');
        // $notification = ;

        return new JsonResponse([
            'status' => 'success',
            'notification' => $notification,
        ]);
    }

    /**
     * Returns a list of notifications
     * Based on filter preference
     * @return JsonResponse
     * @throws Exception
     *
     */
    public function getList(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        // ojm $filter isn't going to work, its a urlSegment
        $filter = $request->getAttribute('filter');
        $offset = $request->getAttribute('offset');
        $limit = $request->getAttribute('limit');

        // $notifications = $this->manager->getList();
        // $loadNext

        return new JsonResponse([
            'status' => 'success',
            'notification' => $notifications,
            'load-next' => $loadNnext
        ]);
    }
}

// GET
// count. response['count']
// settings. (mobile) response['toggles']
// single. response['notification']
// list/all/default. $limit $offset $filter response['notifications', 'load-next']

// POST
// settings. $id, $toggle
// token.
// test.
