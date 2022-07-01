<?php

namespace Minds\Core\FeedNotices;

use Minds\Core\Di\Di;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Controller for FeedNotices.
 */
class Controller
{
    /**
     * Constructor.
     * @param ?Manager $manager - feed notice manager.
     */
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('FeedNotices\Manager');
    }

    /**
     * Get notices for logged in user.
     * @param ServerRequest $request - request object.
     * @return JsonResponse - response.
     */
    public function getNotices(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');

        $notices = $this->manager->getNotices($user);

        return new JsonResponse([
            'status' => 'success',
            'notices' => $notices
        ]);
    }
}
