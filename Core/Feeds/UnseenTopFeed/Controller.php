<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\UnseenTopFeed\ResponseBuilders\UnseenTopFeedResponseBuilder;
use Minds\Core\Feeds\UnseenTopFeed\Validators\UnseenTopFeedRequestValidator;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The controller to handle the requests related to the feed for unseen top posts
 */
class Controller
{
    public function __construct(
        private ?ManagerInterface $manager = null
    ) {
        $this->manager = $this->manager ?? Di::_()->get("Feeds\UnseenTopFeed\Manager");
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
        $response = $this->manager->getUnseenTopEntities($loggedInUser, $totalEntitiesToRetrieve);

        return $responseBuilder->buildSuccessfulResponse($response);
    }
}
