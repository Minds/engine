<?php

declare(strict_types=1);

namespace Minds\Core\Payments;

use Minds\Api\Exportable;
use Minds\Core\Payments\Models\GetPaymentsOpts;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Controller for Payments Module.
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
    }

    /**
     * Get payments for logged in user.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
//    #[OA\Get(
//        path: '/api/v3/payments',
//        parameters: [
//            new OA\Parameter(
//                name: "endingBefore",
//                in: "query",
//                required: false,
//                schema: new OA\Schema(type: 'string')
//            ),
//            new OA\Parameter(
//                name: "limit",
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
    public function getPayments(ServerRequest $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");
        $queryParams = $request->getQueryParams();

        $opts = new GetPaymentsOpts();

        if ($endingBefore = $queryParams['endingBefore'] ?? false) {
            $opts->setEndingBefore($endingBefore);
        }

        if ($limit = $queryParams['limit'] ?? false) {
            $opts->setLimit((int) $limit);
        }

        $response = $this->manager
            ->setUserGuid($loggedInUser->getGuid())
            ->getPayments($opts);

        return new JsonResponse([
            'status' => 'success',
            'data' => Exportable::_($response['data']),
            'has_more' => $response['has_more']
        ]);
    }
}
