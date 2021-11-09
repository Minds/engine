<?php

namespace Minds\Core\SocialCompass;

use Cassandra\Bigint;
use Minds\Core\Di\Di;
use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Core\SocialCompass\ResponseBuilders\GetQuestionsResponseBuilder;
use Minds\Core\SocialCompass\ResponseBuilders\StoreAnswersResponseBuilder;
use Minds\Core\SocialCompass\ResponseBuilders\UpdateAnswersResponseBuilder;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The controller for the Social Compass module
 */
class Controller
{
    private ?User $loggedInUser;

    public function __construct(
        private ?ManagerInterface $manager = null
    ) {
        $this->manager = $this->manager ?? new Manager();

        $activeSession = Di::_()->get("Sessions\ActiveSession");
        $this->loggedInUser = $activeSession->getUser();
    }

    /**
     * Returns the response containing the current set of questions for the Social Compass.
     * If the user had provided answers before then the current value of the questions will be
     * updated to reflect it
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getQuestions(ServerRequestInterface $request): JsonResponse
    {
        $result = $this->manager->retrieveSocialCompassQuestions();
        $responseBuilder = new GetQuestionsResponseBuilder();
        return $responseBuilder->build($result);
    }

    /**
     * Returns a successful response if the answers to the Social Compass questions
     * have been stored correctly, returns a Bad Request response otherwise.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function storeAnswers(ServerRequestInterface $request): JsonResponse
    {
        $answers = $this->getAnswersArrayFromRequestBody($request);

        $responseBuilder = new StoreAnswersResponseBuilder();

        $result = $this->manager->storeSocialCompassAnswers($answers);

        return $responseBuilder->buildResponse($result);
    }

    /**
     * @param ServerRequestInterface $request
     * @return AnswerModel[]
     * @throws UserErrorException
     */
    private function getAnswersArrayFromRequestBody(ServerRequestInterface $request): array
    {
        $requestBody = json_decode($request->getBody()->getContents(), true);

        if (empty($requestBody["social-compass-answers"])) {
            throw new UserErrorException("The 'social-compass-answers' property must be provided and must have at least one entry");
        }

        $answers = [];
        foreach ($requestBody["social-compass-answers"] as $questionId => $answerValue) {
            $answers[] = new AnswerModel(
                new Bigint($this->loggedInUser?->getGuid()),
                $questionId,
                $answerValue
            );
        }

        return $answers;
    }

    /**
     * Returns a successful response if the answers to the Social Compass questions
     * have been updated correctly, returns a Bad Request response otherwise.
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Zend\Diactoros\Response\JsonResponse
     * @throws UserErrorException
     */
    public function updateAnswers(ServerRequestInterface $request): JsonResponse
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
