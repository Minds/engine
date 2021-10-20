<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\Session;
use Minds\Core\SocialCompass\ResponseBuilders\GetQuestionsResponseBuilder;
use Minds\Core\SocialCompass\ResponseBuilders\StoreAnswersResponseBuilder;
use Minds\Core\SocialCompass\ResponseBuilders\UpdateAnswersResponseBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequestFactory;

class Manager implements ManagerInterface
{
    private string $currentQuestionSetVersion = "1";

    public function __construct(
        private ?ServerRequestInterface $request = null,
        private ?RepositoryInterface $repository = null
    )
    {
        $this->request = $this->request ?? ServerRequestFactory::fromGlobals();
        $this->repository = $this->repository ?? new Repository();
    }

    public function setRequest(ServerRequestInterface $request) : void
    {
        $this->request = $request;
    }

    public function retrieveSocialCompassQuestions(): JsonResponse
    {
        return (new GetQuestionsResponseBuilder($this->currentQuestionSetVersion))->build($this->repository);
    }

    public function storeSocialCompassAnswers(): JsonResponse
    {
        $requestBody = json_decode($this->request->getBody()->getContents());
        $responseBuilder = new StoreAnswersResponseBuilder();

        if (empty($requestBody->{"social-compass-answers"})) {
            return $responseBuilder->buildBadRequestResponse("The 'social-compass-answers' property must be provided and must have at least one entry");
        }

        $answers = (array) $requestBody->{"social-compass-answers"};
        $userGuid = Session::getLoggedInUserGuid();

        return $responseBuilder->buildResponse($this->repository->storeAnswers($userGuid, $answers));
    }

    public function updateSocialCompassAnswers(): JsonResponse
    {
        $requestBody = json_decode($this->request->getBody()->getContents());
        $responseBuilder = new UpdateAnswersResponseBuilder();

        if (empty($requestBody->{"social-compass-answers"})) {
            return $responseBuilder->buildBadRequestResponse("The 'social-compass-answers' property must be provided and must have at least one entry");
        }

        $answers = (array) $requestBody->{"social-compass-answers"};
        $userGuid = Session::getLoggedInUserGuid();

        return $responseBuilder->buildResponse($this->repository->storeAnswers($userGuid, $answers));
    }
}
