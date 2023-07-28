<?php

namespace Minds\Core\Feeds\Supermind;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Supermind\Builders\Response\SupermindFeedResponseBuilder;
use Minds\Core\Feeds\Supermind\Validators\SupermindFeedRequestValidator;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Feeds\Superminds\Manager');
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exceptions\SupermindFeedBadRequestException
     * @throws Exception
     */
    //    #[OA\Get(
    //        path: '/api/v3/newsfeed/superminds',
    //        parameters: [
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
    //        ]
    //    )]
    public function getFeed(ServerRequestInterface $request): JsonResponse
    {
        $responseBuilder = new SupermindFeedResponseBuilder();
        $requestValidator = new SupermindFeedRequestValidator();

        if (!$requestValidator->validate($request->getQueryParams())) {
            $responseBuilder->throwBadRequestResponse($requestValidator->getErrors());
        }

        $response = $this->manager->getSupermindActivities($request->getQueryParams()['limit']);

        return $responseBuilder->buildSuccessfulResponse($response);
    }
}
