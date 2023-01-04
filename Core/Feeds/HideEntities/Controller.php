<?php
namespace Minds\Core\Feeds\HideEntities;

use Minds\Core\Di\Di;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get(Manager::class);
    }

    /**
     * Will mark an entity as hidden and save to database
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function hideEntity(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        $entityGuid = $request->getAttribute('parameters')['entityGuid'];
    
        if ($this->manager->withUser($user)->hideEntityByGuid($entityGuid)) {
            return new JsonResponse(
                [],
                status: 201
            );
        }

        // Unkown error
        return new JsonResponse(
            [],
            status: 500
        );
    }
}
