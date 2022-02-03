<?php

namespace Minds\Core\AccountQuality;

use Minds\Core\AccountQuality\ResponseBuilders\GetAccountQualityScoreResponseBuilder;
use Minds\Core\AccountQuality\Validators\GetAccountQualityScoreRequestValidator;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The controller for the Account Quality module
 */
class Controller
{
    public function __construct(
        private ?ManagerInterface $manager = null
    ) {
        $this->manager = $this->manager ?? new Manager();
    }

    /**
     * Http route: api/v3/account-quality/:targetUserGuid
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function getAccountQualityScore(ServerRequestInterface $request): JsonResponse
    {
        $parameters = $request->getAttributes()["parameters"];
        $requestValidator = new GetAccountQualityScoreRequestValidator();

        $responseBuilder = new GetAccountQualityScoreResponseBuilder();

        if (!$requestValidator->validate($parameters)) {
            return $responseBuilder->buildBadRequestResponse($requestValidator->getErrors());
        }

        $results = $this->manager->getAccountQualityScore($parameters['targetUserGuid']);

        return $responseBuilder->buildSuccessfulResponse($results);
    }
}
