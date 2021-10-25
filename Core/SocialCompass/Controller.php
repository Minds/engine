<?php

namespace Minds\Core\SocialCompass;

use Minds\Api\Exportable;
use Minds\Core\SocialCompass\ResponseBuilders\GetQuestionsResponseBuilder;
use Minds\Core\SocialCompass\ResponseBuilders\StoreAnswersResponseBuilder;
use NotImplementedException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?ManagerInterface $manager = null
    ) {
        $this->manager = $this->manager ?? new Manager();
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getQuestions(ServerRequestInterface $request) : JsonResponse
    {
        $result = $this->manager->retrieveSocialCompassQuestions();
        $responseBuilder = new GetQuestionsResponseBuilder();
        return $responseBuilder->build($result);
    }

    public function storeAnswers(ServerRequestInterface $request) : JsonResponse
    {
        $requestBody = json_decode($request->getBody()->getContents());
        $responseBuilder = new StoreAnswersResponseBuilder();

        if (empty($requestBody->{"social-compass-answers"})) {
            $responseBuilder->buildBadRequestResponse("The 'social-compass-answers' property must be provided and must have at least one entry");
        }

        $answers = (array) $requestBody->{"social-compass-answers"};

        $result = $this->manager->storeSocialCompassAnswers($answers);

        return $responseBuilder->buildResponse($result);
    }

    public function updateAnswers(ServerRequestInterface $request) : JsonResponse
    {
        $requestBody = json_decode($request->getBody()->getContents());
        $responseBuilder = new UpdateAnswersResponseBuilder();

        if (empty($requestBody->{"social-compass-answers"})) {
            $responseBuilder->buildBadRequestResponse("The 'social-compass-answers' property must be provided and must have at least one entry");
        }

        $answers = (array) $requestBody->{"social-compass-answers"};

        $result = $this->manager->updateSocialCompassAnswers($answers);

        return $responseBuilder->buildResponse($result);
    }
}
