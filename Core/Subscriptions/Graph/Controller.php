<?php
namespace Minds\Core\Subscriptions\Graph;

use Exception;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Subscriptions Controller
 * @package Minds\Core\Subscriptions
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
        $this->manager = $manager ?: new Manager();
    }

    /**
     * Gets the list of subscriptions
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getSubscriptions(ServerRequest $request): JsonResponse
    {
        return $this->getByType('subscriptions', $request);
    }

    /**
     * Gets the list of subscribers
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getSubscribers(ServerRequest $request): JsonResponse
    {
        throw new Exception('Not Implemented');
        // return $this->getByType('subscribers', $request);
    }

    /**
     * Gets a list by type
     * @internal
     * @param string $type
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    protected function getByType(string $type, ServerRequest $request): JsonResponse
    {
        $guid = $request->getAttribute('parameters')['guid'] ?? null;

        if (!$guid) {
            throw new Exception('Invalid GUID');
        }

        $this->manager
            ->setUserGuid($guid);

        return new JsonResponse([
            'status' => 'success',
            'entities' => $this->manager->getList(
                (new RepositoryGetOptions())
                    ->setType($type)
                    ->setSearchQuery($request->getQueryParams()['q'] ?? '')
                    ->setLimit((int) ($request->getQueryParams()['limit'] ?? 12))
                    ->setOffset((int) ($request->getQueryParams()['load-next'] ?? 0))
            ),
        ]);
    }
}
