<?php
namespace Minds\Core\Notifications;

use Minds\Entities\User;
use Minds\Core;
use Minds\Core\Di\Di;
use Exception;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\Manager;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Minds\Core\Queue\Client as QueueClient;
use Minds\Entities;

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
     * @throws UserErrorException
     *
     */
    public function getSettings(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

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
        $guid = $request->getQueryParams()['guid'];

        if (!$guid) {
            throw new UserErrorException("Guid required");
        }

        /** @var User */
        $user = $request->getAttribute('_user');

        $this->manager->setUser($user);
        $notification = $this->manager->getSingle($guid);

        if (!$notification) {
            return new JsonResponse([
                'status' => 'success',
            ]);
        }

        return new JsonResponse([
            'status' => 'success',
            'notification' => $notification,
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
            $this->counters->resetCounter();
        }

        if ($filter === 'list' || $filter === 'all') {
            $filter = '';
        }

        if ($limit > static::MAX_NOTIFICATIONS_PER_PAGE) {
            $limit = static::MAX_NOTIFICATIONS_PER_PAGE;
        }

        $this->manager->setUser($user);
        $notifications = $this->manager->getList([
                    'type' => $filter,
                    'limit' => $limit,
                    'offset' => $offset
                ]);

        if (!$notifications) {
            return new JsonResponse([
                'status' => 'success'
            ]);
        }

        $loadNext = (string) $notifications->getPagingToken();

        return new JsonResponse([
            'status' => 'success',
            'notifications' => $notifications,
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

        $this->manager->updateSettings($user->guid, $id, $toggle);

        return new JsonResponse([
            'status' => 'success'
        ]);
    }

    /**
     * @return void
     * @throws UserErrorException
     */
    public function createToken(ServerRequest $request): void
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $body = $request->getParsedBody();

        $service = $body['service'];
        $passed_token = $body['token'];

        if (!$service || !$passed_token) {
            throw new UserErrorException("Service and token required");
        }

        $token = \Surge\Token::create([
            'service' => $service,
            'token' => $passed_token
        ]);

        (new Core\Data\Call('entities'))
            ->insert($user->guid, [ 'surge_token' => $token ]);
    }

    /**
     * For notification testing
     * @return void
     */
    public function test(ServerRequest $request): void
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $body = $request->getParsedBody();

        QueueClient::build()
            ->setQueue('Push')
            ->send([
                'user_guid' => $user->guid,
                'uri' => $_POST['uri'] ?? 'https://www.minds.com/' . $user->username,
                'title' => $body['title'] ?? 'Hello there',
                'message' => $body['message'] ?? 'This is a test',
            ]);
    }
}
