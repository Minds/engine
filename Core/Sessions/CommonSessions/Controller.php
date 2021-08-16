<?php
namespace Minds\Core\Sessions\CommonSessions;

use Minds\Entities\User;
use Exception;
use Minds\Api\Exportable;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Sessions\CommonSessions\CommonSession;
use Minds\Core\Sessions\CommonSessions\Manager;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * CommonSessions Controller
 * @package Minds\Core\Sessions\CommonSessions
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
     * Returns all sessions, both jwt and oauth
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     *
     */
    public function getAll(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $sessions = $this->manager->getAll($user);

        if (!$sessions) {
            throw new UserErrorException("Unable to retrieve sessions");
        }

        return new JsonResponse([
            'status' => 'success',
            'sessions' => Exportable::_($sessions)
        ]);
    }

    /**
     * Deletes a common session
     * @param ServerRequest $request
     * @return JsonResponse
     *
     */
    public function deleteSession(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $params = $request->getQueryParams();

        $id = $params['id'] ?? null;
        $platform = $params['platform'] ?? null;

        if (!$id) {
            throw new UserErrorException("Session id required");
        }

        if (!$platform) {
            throw new UserErrorException("Platform required - browser or mobile");
        }

        $commonSession = new CommonSession();
        $commonSession->setUserGuid($user->getGuid())
            ->setId($id)
            ->setPlatform($platform);

        $response = $this->manager->delete($commonSession);

        if (!$response) {
            throw new UserErrorException("Session could not be deleted");
        }

        return new JsonResponse([
            'status' => 'success',
        ]);
    }

    /**
     * Deletes all sessions for the logged-in user.
     * @param ServerRequest $request - request object from client
     * @return JsonResponse $response - object passed to client.
     */
    public function deleteAllSessions(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');
        $this->manager->deleteAll($user);

        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
