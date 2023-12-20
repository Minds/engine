<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Exception;
use Minds\Api\Exportable;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Exceptions\SupermindRequestExpiredException;
use Minds\Core\Supermind\Exceptions\SupermindRequestIncorrectStatusException;
use Minds\Core\Supermind\Exceptions\SupermindUnauthorizedSenderException;
use Minds\Core\Supermind\Validators\SupermindCountRequestsValidator;
use Minds\Core\Supermind\Validators\SupermindGetRequestsValidator;
use Minds\Core\Supermind\Validators\SupermindLiveReplyValidator;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Stripe\Exception\ApiErrorException;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?Manager $manager = null,
        private ?Logger $logger = null
    ) {
        $this->manager ??= Di::_()->get("Supermind\Manager");
        $this->logger ??= Di::_()->get("Logger");
    }

    /**
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ApiErrorException
     * @throws LockFailedException
     * @throws SupermindNotFoundException
     * @throws SupermindRequestExpiredException
     * @throws SupermindRequestIncorrectStatusException
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

        $supermindRequestID = $request->getAttribute("parameters")["guid"];
        $this->manager->revokeSupermindRequest($supermindRequestID);

        return new JsonResponse([]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ApiErrorException
     * @throws SupermindRequestExpiredException
     * @throws SupermindRequestIncorrectStatusException
     * @throws LockFailedException
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
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

        $supermindRequestID = $request->getAttribute("parameters")["guid"];
        $this->manager->rejectSupermindRequest($supermindRequestID);

        return new JsonResponse([]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException
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

        $requestValidator = new SupermindGetRequestsValidator();

        if (!$requestValidator->validate($request->getQueryParams())) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: $requestValidator->getErrors()
            );
        }

        ['limit' => $limit, 'offset' => $offset, 'status' => $status] = $request->getQueryParams();

        $response = $this->manager->getReceivedRequests(
            offset: (int) $offset,
            limit: (int) $limit,
            status: $status ? SupermindRequestStatus::from((int) $status) : null
        );
        return new JsonResponse(Exportable::_($response));
    }

    /**
     * Count Supermind offers in the logged in users inbox.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    //    #[OA\Get(
    //        path: '/api/v3/supermind/inbox/count',
    //        parameters: [
    //            new OA\Parameter(
    //                name: "status",
    //                in: "query",
    //                required: false,
    //                schema: new OA\Schema(type: 'integer')
    //            )
    //        ],
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 400, description: "Bad Request"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //        ]
    //    )]
    public function countSupermindInboxRequests(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");
    
        $requestValidator = new SupermindCountRequestsValidator();
        if (!$requestValidator->validate($request->getQueryParams())) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: $requestValidator->getErrors()
            );
        }
    
        $this->manager->setUser($loggedInUser);

        ['status' => $status] = $request->getQueryParams();

        $count = $this->manager->countReceivedRequests(
            status: $status ? SupermindRequestStatus::from((int) $status) : null
        );

        return new JsonResponse([ 'count' => $count ]);
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

        ['limit' => $limit, 'offset' => $offset, 'status' => $status] = $request->getQueryParams();

        $response = $this->manager->getSentRequests(
            offset: (int) $offset,
            limit: (int) $limit,
            status: $status ? SupermindRequestStatus::from((int) $status) : null
        );
        return new JsonResponse(Exportable::_($response));
    }

    /**
     * Count Supermind offers in the logged in users outbox.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    //    #[OA\Get(
    //        path: '/api/v3/supermind/outbox/count',
    //        parameters: [
    //            new OA\Parameter(
    //                name: "status",
    //                in: "query",
    //                required: false,
    //                schema: new OA\Schema(type: 'integer')
    //            )
    //        ],
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 400, description: "Bad Request"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //        ]
    //    )]
    public function countSupermindOutboxRequests(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");

        $requestValidator = new SupermindCountRequestsValidator();
        if (!$requestValidator->validate($request->getQueryParams())) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: $requestValidator->getErrors()
            );
        }

        $this->manager->setUser($loggedInUser);

        $status = (int) ($request->getQueryParams()['status'] ?? null);

        $count = $this->manager->countSentRequests(
            status: $status ? SupermindRequestStatus::from((int) $status) : null
        );

        return new JsonResponse([ 'count' => $count ]);
    }


    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws SupermindNotFoundException
     * @throws ForbiddenException
     */
    //    #[OA\Get(
    //        path: '/api/v3/supermind/:guid',
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //        ]
    //    )]
    public function getSupermindRequest(ServerRequestInterface $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        $supermindRequestID = $request->getAttribute("parameters")["guid"];
        $response = $this->manager
            ->setUser($user)
            ->getRequest((string) $supermindRequestID);
        return new JsonResponse(Exportable::_([$response]));
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    //    #[OA\Post(
    //        path: '/api/v3/supermind/bulk',
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 403, description: "Forbidden"),
    //        ]
    //    )]
    public function createBulkSupermindRequest(ServerRequestInterface $request): JsonResponse
    {
        try {
            $this->manager->createBulkSupermindRequest($request->getParsedBody());
        } catch (Exception $e) {
            // Catch any exception thrown and continue to the next action
            $this->logger->error($e->getMessage(), [
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
                'trace_string' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
        return new JsonResponse([]);
    }

    /**
     * Accept a live Supermind request.
     * @param ServerRequestInterface $request - request to accept.
     * @return JsonResponse - empty JSON response on success.
     * @throws ApiErrorException
     * @throws SupermindRequestExpiredException
     * @throws SupermindRequestIncorrectStatusException
     * @throws LockFailedException
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
     */
    //    #[OA\Post(
    //        path: '/api/v3/supermind/:guid/accept-live',
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
    public function acceptLiveSupermindRequest(ServerRequestInterface $request): JsonResponse
    {
        $requestValidator = new SupermindLiveReplyValidator();
        if (!$requestValidator->validate($request)) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: $requestValidator->getErrors()
            );
        }

        $this->manager->acceptSupermindRequest(
            $request->getAttribute("parameters")["guid"]
        );

        return new JsonResponse([]);
    }
}
