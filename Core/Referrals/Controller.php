<?php
declare(strict_types=1);

namespace Minds\Core\Referrals;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 *
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null,
        private ?Logger $logger = null
    ) {
        $this->manager ??= Di::_()->get('Referrals\Manager');
        $this->logger ??= Di::_()->get('Logger');
    }

    // #[OA\Get(
    //    path: '/api/v3/referrals/metrics',
    //    responses: [
    //        new OA\Response(response: 200, description: "Ok"),
    //        new OA\Response(response: 400, description: "Bad Request"),
    //        new OA\Response(response: 401, description: "Unauthorized"),
    //        new OA\Response(response: 403, description: "Forbidden"),
    //        new OA\Response(response: 404, description: "Not found")
    //        new OA\Response(response: 500, description: "Internal Server Error")
    //    ]
    // )]
    public function getMetrics(ServerRequestInterface $request): JsonResponse
    {
        /**
         * @type User $user
         */
        $user = $request->getAttribute('_user');
        
        $response = $this->manager->getMetrics(
            user: $user
        );

        return new JsonResponse($response);
    }
}
