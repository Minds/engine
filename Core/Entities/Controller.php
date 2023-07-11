<?php

declare(strict_types=1);

namespace Minds\Core\Entities;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Exceptions\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ACL $acl = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->acl ??= Di::_()->get('Security\ACL');
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    //    #[OA\Get(
    //        path: '/api/v3/entities/:guid',
    //        parameters: [
    //            new OA\Parameter(
    //                name: "guid",
    //                in: "path",
    //                required: true,
    //                schema: new OA\Schema(type: 'string')
    //            )
    //        ],
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 400, description: "Bad Request"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //            new OA\Response(response: 404, description: "Not found")
    //        ]
    //    )]
    public function getEntity(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");
        $ref = $request->getAttribute('parameters')['ref'];

        $entity = null;
        if (!is_numeric($ref) && is_string($ref)) {
            $entity = $this->entitiesBuilder->getByUserByIndex($ref);
        }

        if (!$entity) {
            $entity = $this->entitiesBuilder->single($ref);
        }

        if (!$entity) {
            throw new NotFoundException();
        }

        if (!$this->acl->read($entity, $loggedInUser)) {
            throw new ForbiddenException();
        }

        return new JsonResponse($entity->export());
    }
}
