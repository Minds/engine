<?php

namespace Minds\Core\SocialCompass;

use Cassandra\Bigint;
use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Core\SocialCompass\ResponseBuilders\GetQuestionsResponseBuilder;
use Minds\Core\SocialCompass\ResponseBuilders\StoreAnswersResponseBuilder;
use Minds\Core\SocialCompass\ResponseBuilders\UpdateAnswersResponseBuilder;
use Minds\Core\SocialCompass\Validators\AnswersCollectionValidator;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
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
        $this->loggedInUser = $request->getAttribute('_user');
        $result = $this->manager->retrieveSocialCompassQuestions();
        $responseBuilder = new GetQuestionsResponseBuilder();
        return $responseBuilder->build($result, $this->loggedInUser != null);
    }

    /**
     * Returns a successful response if the answers to the Social Compass questions
     * have been stored correctly, returns a Bad Request response otherwise.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException|ServerErrorException
     */
    public function storeAnswers(ServerRequestInterface $request): JsonResponse
    {
        $this->loggedInUser = $request->getAttribute('_user');

        $answers = $this->getAnswersArrayFromRequestBody($request);

        $this->validateAnswers($answers);

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
        $this->loggedInUser = $request->getAttribute('_user');

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
     * @throws UserErrorException
     */
    private function validateAnswers(array $answers): void
    {
        $validator = new AnswersCollectionValidator($answers);
        $validator->validate();
        if ($validator->errors()?->count() > 0) {
            throw new UserErrorException("There were some errors found when validating the answers provided", 0, $validator->errors());
        }
    }

    /**
     * Returns a successful response if the answers to the Social Compass questions
     * have been updated correctly, returns a Bad Request response otherwise.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws UserErrorException|ServerErrorException
     */
    public function updateAnswers(ServerRequestInterface $request): JsonResponse
    {
        $this->loggedInUser = $request->getAttribute('_user');
        
        $answers = $this->getAnswersArrayFromRequestBody($request);

        $this->validateAnswers($answers);

        $responseBuilder = new UpdateAnswersResponseBuilder();

        $result = $this->manager->updateSocialCompassAnswers($answers);

        return $responseBuilder->buildResponse($result);
    }
}
