<?php

namespace Minds\Core\Feeds\ClusteredRecommendations;

use Exception;
use Minds\Core\Feeds\ClusteredRecommendations\ResponseBuilders\ClusteredRecommendationsResponseBuilder;
use Minds\Core\Feeds\ClusteredRecommendations\Validators\GetFeedRequestValidator;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Controller class for the clustered recommendations
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
    }

    /**
     * Returns the feed entities, 404 Bad request otherwise
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getFeed(ServerRequestInterface $request): JsonResponse
    {
        $responseBuilder = new ClusteredRecommendationsResponseBuilder();
        $requestValidator = new GetFeedRequestValidator();
        $queryParams = $request->getQueryParams();

        if (!$requestValidator->validate($queryParams)) {
            $responseBuilder->throwBadRequestResponse($requestValidator->getErrors());
        }

        $limit = (int) $queryParams['limit'] ?? 12;
        $unseen = (bool) $queryParams['unseen'] ?? false;

        $this->manager->setUser($request->getAttribute('_user'));

        $results = $this->manager->getList($limit, $unseen);

        return $responseBuilder->successfulResponse($results);
    }
}
