<?php

namespace Minds\Core\Feeds\Subscribed;

use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Subscribed\ResponseBuilders\SubscribedLatestCountResponseBuilder;
use Minds\Core\Feeds\Subscribed\Validators\SubscribedLatestCountRequestValidator;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The controller to handle the requests related to the subscribed feed
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager = $this->manager ?? Di::_()->get("Feeds\Subscribed\Manager");
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function getLatestCount(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $responseBuilder = new SubscribedLatestCountResponseBuilder();
        $requestValidator = new SubscribedLatestCountRequestValidator();

        if (!$requestValidator->validate($request->getQueryParams())) {
            $responseBuilder->buildBadRequestResponse($requestValidator->getErrors());
        }

        $fromTimestamp = $request->getQueryParams()["from_timestamp"];
        $count = $this->manager->getLatestCount($loggedInUser, $fromTimestamp);

        return $responseBuilder->buildSuccessfulResponse($count);
    }
}
