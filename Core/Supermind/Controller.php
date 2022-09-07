<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Minds\Api\Exportable;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Exceptions\SupermindUnauthorizedSenderException;
use Minds\Core\Supermind\Validators\SupermindGetRequestsValidator;
use Minds\Exceptions\UserErrorException;
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
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ApiErrorException
     * @throws LockFailedException
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
     */
//    #[OA\Delete(
//        path: '/api/v3/supermind/:guid',
//        parameters: [
//            new OA\Parameter(
//                name: "guid",
//                in: "path",
//                required: true,
//                schema: new OA\Schema(type: 'integer')
//            )
//        ],
//        responses: [
//            new OA\Response(response: 200, description: "Ok"),
//            new OA\Response(response: 400, description: "Bad Request"),
//            new OA\Response(response: 401, description: "Unauthorized"),
//            new OA\Response(response: 403, description: "Forbidden"),
//            new OA\Response(response: 404, description: "Not found")
//        ]
//    )]
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
     * @throws ApiErrorException
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
     * @throws LockFailedException
     */
//    #[OA\Post(
//        path: '/api/v3/supermind/:guid/reject',
//        parameters: [
//            new OA\Parameter(
//                name: "guid",
//                in: "path",
//                required: true,
//                schema: new OA\Schema(type: 'integer')
//            )
//        ],
//        responses: [
//            new OA\Response(response: 200, description: "Ok"),
//            new OA\Response(response: 400, description: "Bad Request"),
//            new OA\Response(response: 401, description: "Unauthorized"),
//            new OA\Response(response: 403, description: "Forbidden"),
//            new OA\Response(response: 404, description: "Not found")
//        ]
//    )]
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
     */
//    #[OA\Get(
//        path: '/api/v3/supermind/inbox',
//        parameters: [
//            new OA\Parameter(
//                name: "offset",
//                in: "query",
//                required: true,
//                schema: new OA\Schema(type: 'integer')
//            ),
//            new OA\Parameter(
//                name: "limit",
//                in: "query",
//                required: true,
//                schema: new OA\Schema(type: 'integer')
//            )
//        ],
//        responses: [
//            new OA\Response(response: 200, description: "Ok"),
//            new OA\Response(response: 400, description: "Bad Request"),
//            new OA\Response(response: 401, description: "Unauthorized"),
//        ]
//    )]
    public function getSupermindInboxRequests(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");
        $this->manager->setUser($loggedInUser);

        ['limit' => $limit, 'offset' => $offset] = $request->getQueryParams();

        $response = $this->manager->getReceivedRequests((int) $offset, (int) $limit);
        return new JsonResponse(Exportable::_($response));
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException
     */
//    #[OA\Get(
//        path: '/api/v3/supermind/outbox',
//        parameters: [
//            new OA\Parameter(
//                name: "offset",
//                in: "query",
//                required: true,
//                schema: new OA\Schema(type: 'integer')
//            ),
//            new OA\Parameter(
//                name: "limit",
//                in: "query",
//                required: true,
//                schema: new OA\Schema(type: 'integer')
//            )
//        ],
//        responses: [
//            new OA\Response(response: 200, description: "Ok"),
//            new OA\Response(response: 400, description: "Bad Request"),
//            new OA\Response(response: 401, description: "Unauthorized"),
//        ]
//    )]
    public function getSupermindOutboxRequests(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");
        $this->manager->setUser($loggedInUser);

        $requestValidator = new SupermindGetRequestsValidator();

        if (!$requestValidator->validate($request->getQueryParams())) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: $requestValidator->getErrors()
            );
        }

        ['limit' => $limit, 'offset' => $offset] = $request->getQueryParams();

        $response = $this->manager->getSentRequests((int) $offset, (int) $limit);
        return new JsonResponse(Exportable::_($response));
    }
}
