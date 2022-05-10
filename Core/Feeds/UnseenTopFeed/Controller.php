<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Minds\Core\Di\Di;
use Minds\Core\Feeds\UnseenTopFeed\ResponseBuilders\UnseenTopFeedResponseBuilder;
use Minds\Core\Feeds\UnseenTopFeed\Validators\UnseenTopFeedRequestValidator;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;

/**
 * The controller to handle the requests related to the feed for unseen top posts
 */
class Controller
{
    public function __construct(
        private ?ElasticSearchManager $elasticSearchManager = null
    ) {
        $this->elasticSearchManager = $this->elasticSearchManager ?? Di::_()->get("Feeds\Elastic\Manager");
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function getUnseenTopFeed(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $responseBuilder = new UnseenTopFeedResponseBuilder();
        $requestValidator = new UnseenTopFeedRequestValidator();

        if (!$requestValidator->validate($request->getQueryParams())) {
            $responseBuilder->buildBadRequestResponse($requestValidator->getErrors());
        }

        $totalEntitiesToRetrieve = $request->getQueryParams()["limit"];
        $response = $this->elasticSearchManager->getList([
            'limit' => $totalEntitiesToRetrieve,
            'type' => 'activity',
            'algorithm' => 'top',
            'subscriptions' => $loggedInUser->getGuid(),
            'period' => 'all', // legacy option
            'unseen' => true,
        ]);
        // This endpoint doesn't support pagination yet
        $response->setPagingToken(null);

        return $responseBuilder->buildSuccessfulResponse($response);
    }
}
