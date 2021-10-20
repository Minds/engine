<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Cassandra\Rows;
use Minds\Core\Session;
use Minds\Core\SocialCompass\RepositoryInterface;
use Zend\Diactoros\Response\JsonResponse;

class GetQuestionsResponseBuilder
{
    public function __construct(
        private string $currenQuestionSetVersion
    )
    {}

    /**
     * Build the response object for the Social Compass getQuestions endpoint
     * @param RepositoryInterface|null $repository
     * @return JsonResponse
     */
    public function build(?RepositoryInterface $repository = null) : JsonResponse
    {
        $manifest = "Minds\Core\SocialCompass\Questions\Manifests\QuestionsManifestV{$this->currenQuestionSetVersion}";
        $questionsList = new $manifest();

        $results = [];

        foreach ($questionsList::Questions as $questionId)
        {
            $userGuid = Session::getLoggedInUserGuid();
            $question = new $questionId();
            $answer = $repository->getAnswerByQuestionId($userGuid, $question->questionId);
            if (isset($answer) && $answer->count() > 0) {
                $question->currentValue = $answer->first()["current_value"];
            }

            array_push($results, $question);
        }

        return new JsonResponse($results);
    }
}
