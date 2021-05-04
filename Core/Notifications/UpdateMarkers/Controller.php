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

//ojm need to move and update this spec test
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
        // ojm
        // .get('api/v2/notifications/markers', {type: 'group',})

        // ojm confirm that GET request objects are rec'd as query params
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
        // ojm
        // this.http.post('api/v2/notifications/markers/read', opts)

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

    //ojm heartbeat
    /**
     * Returns a hearbeat during live gathering
     * @return JsonResponse
     * @throws Exception
     *
     */
    // public function markGathering(ServerRequest $request): JsonResponse
    // {

    //     return new JsonResponse([
    //         'marker' => $marker->export()
    //     ]);
    // }


    //ojm heartbeat
    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    // public function put($pages)
    // {
    //     $marker = new UpdateMarker;
    //     $marker
    //         ->setUserGuid(Session::getLoggedInUserGuid())
    //         ->setEntityGuid($pages[1])
    //         ->setEntityType('group')
    //         ->setMarker('gathering-heartbeat')
    //         ->setUpdatedTimestamp(time());
    //     $manager = (new Manager());
    //     $manager->pushToSocketRoom($marker);

    //     return Factory::response([
    //         'marker' => $marker->export(),
    //     ]);
    // }
}
