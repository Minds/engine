<?php
namespace Minds\Core\Channels\Groups;

use Exception;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Channel Groups Controller
 * @package Minds\Core\Channels\Groups
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /**
     * Controller constructor.
     * @param $manager
     */
    public function __construct(
        $manager = null
    ) {
        $this->manager = $manager ?: new Manager();
    }

    /**
     * Lists public groups the channel is member of
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getList(ServerRequest $request): JsonResponse
    {
        $guid = $request->getAttribute('parameters')['guid'] ?? null;

        if (!$guid) {
            throw new Exception('Invalid GUID');
        }

        $this->manager
            ->setUserGuid($guid);

        return new JsonResponse([
            'status' => 'success',
            'entities' => $this->manager->getList([
                'pageToken' => $request->getQueryParams()['pageToken'] ?? '',
            ]),
        ]);
    }

    /**
     * Counts the amount of public groups the channel is member of
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function count(ServerRequest $request): JsonResponse
    {
        $guid = $request->getAttribute('parameters')['guid'] ?? null;

        if (!$guid) {
            throw new Exception('Invalid GUID');
        }

        $this->manager
            ->setUserGuid($guid);

        return new JsonResponse([
            'status' => 'success',
            'count' => $this->manager->count(),
        ]);
    }
}
