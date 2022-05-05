<?php

namespace Minds\Core\Notifications\Push\System;

use Exception;
use Minds\Core\Notifications\Push\System\ResponseBuilders\GetHistoryResponseBuilder;
use Minds\Core\Notifications\Push\System\ResponseBuilders\ScheduleResponseBuilder;
use Minds\Exceptions\ServerErrorException;
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

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exception
     */
    public function schedule(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $requestBody = $request->getParsedBody();

        $responseBuilder = new ScheduleResponseBuilder();

        $this->manager->setUser($loggedInUser);

        $response = $this->manager->add($requestBody);

        return $responseBuilder->successfulResponse($response);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ServerErrorException
     */
    public function getHistory(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $responseBuilder = new GetHistoryResponseBuilder();

        $this->manager->setUser($loggedInUser);

        $response = $this->manager->getCompletedRequests();

        return $responseBuilder->successfulResponse($response);
    }
}
