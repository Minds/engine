<?php

namespace Minds\Core\Notifications\Push\System;

use Minds\Core\Notifications\Push\System\ResponseBuilders\GetHistoryResponseBuilder;
use Minds\Core\Notifications\Push\System\ResponseBuilders\ScheduleResponseBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 *
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
    }

    public function schedule(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $requestBody = $request->getParsedBody();

        $responseBuilder = new ScheduleResponseBuilder();

        $this->manager->setUser($loggedInUser);

        $response = $this->manager->add($requestBody);

        return $responseBuilder->successfulResponse($response);
    }

    public function getHistory(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $responseBuilder = new GetHistoryResponseBuilder();

        $this->manager->setUser($loggedInUser);

        $response = $this->manager->getCompletedRequests();

        return $responseBuilder->successfulResponse($response);
    }
}
