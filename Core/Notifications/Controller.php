<?php
namespace Minds\Core\Notifications;

use Minds\Entities\User;
use Minds\Core;
use Minds\Core\Notification;
use Minds\Api\Exportable;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Notifications\Manager;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Minds\Core\Queue\Client as QueueClient;

/**
 * Notifications Controller
 * @package Minds\Core\Notifications
 */
class Controller
{
    /** @var string */
    public const MAX_NOTIFICATIONS_PER_PAGE = 50;

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
    public function getUnreadCount(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        if (!$user) {
            throw new UserErrorException("Invalid user");
        }

        $count = $this->manager
            ->getUnreadCount($user);

        return new JsonResponse([
            'status' => 'success',
            'count' => $count,
        ]);
    }

    /**
     * Returns a user's push notification settings
     * @return JsonResponse
     * @throws UserErrorException
     *
     */
    public function getSettings(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        // TODO
        $toggles = $this->manager->getSettings($user->guid);

        return new JsonResponse([
            'status' => 'success',
            'toggles' => $toggles,
        ]);
    }

    /**
    * Returns single notification
    * @return JsonResponse
    * @throws UserErrorException
    *
    */
    public function getSingle(ServerRequest $request): JsonResponse
    {
        $urn = $request->getAttribute('parameters')['urn'];

        if (!$urn) {
            throw new UserErrorException("Notification urn is required");
        }

        $notification = $this->manager->getByUrn($urn);

        if (!$notification) {
            throw new UserErrorException("Notification not found", 404);
        }

        return new JsonResponse([
            'status' => 'success',
            'notification' => $notification->export(),
        ]);
    }

    /**
     * Returns a list of notifications
     * Based on filter preference
     * @return JsonResponse
     */
    public function getList(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $filter = $request->getQueryParams()['filter'] ?: '';
        $offset = $request->getQueryParams()['offset'] ?: '';
        $limit = $request->getQueryParams()['limit'] ?: 12;

        if (!$offset) {
            $this->manager->resetCount($user);
        }

        if ($limit > static::MAX_NOTIFICATIONS_PER_PAGE) {
            $limit = static::MAX_NOTIFICATIONS_PER_PAGE;
        }

        $opts = new NotificationsListOpts();
        $opts->setToGuid((string) $user->getGuid())
            ->setLimit($limit)
            ->setOffset($offset);

        if ($filter) {
            $opts->setGroupingType($filter);
        }

        $rawNotifications = iterator_to_array($this->manager->getList($opts));
        $notifications = array_column($rawNotifications, 0);
        $loadNext = base64_encode(end(array_column($rawNotifications, 1)));

        if (!$notifications) {
            return new JsonResponse([
                'status' => 'success'
            ]);
        }

        $exportedList = array_values(array_filter(Exportable::_($notifications)->export(), function ($notification) {
            if (!isset($notification['entity'])) {
                return false; // TODO: Delete this notification as the entity is invalid
            }
            if (!isset($notification['from'])) {
                return false;
            }
            return true;
        }));

        return new JsonResponse([
            'status' => 'success',
            'notifications' => $exportedList,
            'load-next' => $loadNext
        ]);
    }

    /**
     * Save a user's notification settings
     * @return JsonResponse
     * @throws UserErrorException
     *
     */
    public function updateSettings(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $body = $request->getParsedBody();

        $id = $body['id'];
        $toggle = $body['toggle'];

        // TODO
        // $this->manager->updateSettings($user->guid, $id, $toggle);

        return new JsonResponse([
            'status' => 'success'
        ]);
    }

    /**
     * Mark a notification as read
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function markAsRead(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        /** @var string */
        $urn = $request->getAttribute('parameters')['urn'];


        if (!$urn) {
            throw new UserErrorException("Notification urn is required");
        }

        $notification = $this->manager->getByUrn($urn);

        if (!$notification) {
            throw new UserErrorException("Notification not found", 404);
        }

        $success = $this->manager->markAsRead($notification, $user);

        return new JsonResponse([
            'status' => $success ? 'success' : 'error',
        ]);
    }
}
