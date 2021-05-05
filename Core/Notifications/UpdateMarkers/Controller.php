<?php
/**
 * Update Markers Controller
 */

namespace Minds\Core\Notifications\UpdateMarkers;

use Minds\Api\Factory;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications\UpdateMarkers\Manager;
use Minds\Core\Notifications\UpdateMarkers\UpdateMarker;
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
     * Returns list of update markers
     * @return JsonResponse
     */
    public function getList(ServerRequest $request): JsonResponse
    {
        $entityType = $request->getQueryParams()['type'] ?? 'group';

        /** @var User */
        $user = $request->getAttribute('_user');

        $opts = [
            'user_guid' => $user->guid,
            'entity_type' => $entityType,
        ];

        $list = $this->manager->getList($opts);

        return new JsonResponse([
            'status' => 'success',
            'markers' => $list,
        ]);
    }

    /**
     * Updates the last read time on a marker
     * @return JsonResponse
     * @throws UserErrorException
     *
     */
    public function readMarker(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $body = $request->getParsedBody();

        $entityGuid = $body['entity_guid'];
        $entityType = $body['entity_type'];
        $marker = $body['marker'];

        if (!$entityGuid || !$entityType || !$marker) {
            throw new UserErrorException("Entity guid, entity type and marker must be set.");
        }

        $marker = new UpdateMarker;
        $marker
            ->setUserGuid($user->guid)
            ->setEntityGuid($entityGuid)
            ->setEntityType($entityType)
            ->setMarker($marker)
            ->setReadTimestamp(time());

        $this->manager->add($marker);

        return new JsonResponse([
            'status' => 'success'
        ]);
    }

    /**
     * Returns a hearbeat during live gathering
     * @return JsonResponse
     * @throws Exception
     *
     */
    public function markGathering(ServerRequest $request): JsonResponse
    {
        $body = $request->getParsedBody();

        $entityGuid = $body['entity_guid'];

        if (!$entityGuid) {
            throw new UserErrorException("entity_guid must be provided");
        }

        $marker = new UpdateMarker;
        $marker
                ->setUserGuid(Session::getLoggedInUserGuid())
                ->setEntityGuid($entityGuid)
                ->setEntityType('group')
                ->setMarker('gathering-heartbeat')
                ->setUpdatedTimestamp(time());
        $this->manager->pushToSocketRoom($marker);

        return new JsonResponse([
            'marker' => $marker->export()
        ]);
    }
}
