<?php

namespace Minds\Core\Recommendations;

use Minds\Core\Di\Di;
use Minds\Core\Recommendations\Injectors\BoostSuggestionInjector;
use Minds\Core\Recommendations\ResponseBuilders\GetRecommendationsResponseBuilder;
use Minds\Core\Recommendations\Validators\GetRecommendationsRequestValidator;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?ManagerInterface $manager = null,
        private ?BoostSuggestionInjector $boostSuggestionInjector = null
    ) {
        $this->manager = $this->manager ?? Di::_()->get("Recommendations\Manager");
        $this->boostSuggestionInjector ??= Di::_()->get(BoostSuggestionInjector::class);
    }

    private function getLoggedInUserFromRequest(ServerRequestInterface $request): ?User
    {
        return $request->getAttribute("_user");
    }

    /**
     * @throws UserErrorException
     */
    public function getRecommendations(ServerRequestInterface $request): JsonResponse
    {
        $user = $this->getLoggedInUserFromRequest($request);

        $responseBuilder = new GetRecommendationsResponseBuilder();

        $requestValidator = new GetRecommendationsRequestValidator();
        if (!$requestValidator->validate($request->getQueryParams())) {
            return $responseBuilder->buildBadRequestResponse($requestValidator->getErrors());
        }

        $response = $this->manager->getRecommendations($user, $request->getQueryParams()["location"], $request->getQueryParams());

        $response['entities'] = $this->boostSuggestionInjector->inject(
            response: $response['entities'],
            targetUser: $user,
            index: 1
        );

        return $responseBuilder->buildSuccessfulResponse($response);
    }
}
