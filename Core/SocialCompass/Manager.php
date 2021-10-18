<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\Session;
use Minds\Core\SocialCompass\ResponseBuilders\GetQuestionsResponseBuilder;
use Minds\Core\SocialCompass\ResponseBuilders\StoreAnswersResponseBuilder;
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

    public function retrieveSocialCompassQuestions(): JsonResponse
    {
        $userGuid = Session::getLoggedInUserGuid();

        $answers = $this->repository->getAnswers(
            $userGuid
        );

        return (new GetQuestionsResponseBuilder($this->currentQuestionSetVersion))->build($answers);
    }

    public function storeSocialCompassAnswers(): JsonResponse
    {
        $requestBody = $this->request->getParsedBody();
        $responseBuilder = new StoreAnswersResponseBuilder();

        if (empty($requestBody['social-compass-answers'])) {
            return $responseBuilder->buildBadRequestResponse();
        }

        $answers = $requestBody['social-compass-answers'];
        $userGuid = Session::getLoggedInUserGuid();

        return $responseBuilder->buildResponse($this->repository->storeAnswers($userGuid, $answers));
    }
}
