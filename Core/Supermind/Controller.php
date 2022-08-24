<?php

namespace Minds\Core\Supermind;

use Minds\Core\Di\Di;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Exceptions\SupermindUnauthorizedSenderException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface;
use Stripe\Exception\ApiErrorException;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get("Supermind\Manager");
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ApiErrorException
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
     */
    #[OA\Delete(
        path: '/api/v3/supermind/:guid',
        parameters: [
            new OA\Parameter(
                name: "guid",
                in: "path",
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Ok"),
            new OA\Response(response: 400, description: "Bad Request"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function revokeSupermindRequest(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");
        $this->manager->setUser($loggedInUser);

        $supermindRequestID = $request->getAttribute("guid");
        $this->manager->revokeSupermindRequest($supermindRequestID);

        return new JsonResponse(['error']);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
     * @throws ApiErrorException
     */
    #[OA\Post(
        path: '/api/v3/supermind/:guid/reject',
        parameters: [
            new OA\Parameter(
                name: "guid",
                in: "path",
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Ok"),
            new OA\Response(response: 400, description: "Bad Request"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function rejectSupermindRequest(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");
        $this->manager->setUser($loggedInUser);

        $supermindRequestID = $request->getAttribute("guid");
        $this->manager->rejectSupermindRequest($supermindRequestID);

        return new JsonResponse([]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws SupermindNotFoundException
     */
    #[OA\Get(
        path: '/api/v3/supermind/inbox',
        responses: [
            new OA\Response(response: 200, description: "Ok"),
            new OA\Response(response: 400, description: "Bad Request"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    #[OA\Get(
        path: '/api/v3/supermind/outbox',
        responses: [
            new OA\Response(response: 200, description: "Ok"),
            new OA\Response(response: 400, description: "Bad Request"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function getSupermindRequests(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");
        $this->manager->setUser($loggedInUser);

        $response = $this->manager->getRequests();
        return new JsonResponse($response);
    }
}
