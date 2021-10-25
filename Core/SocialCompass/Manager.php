<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\Session;
use Minds\Core\SocialCompass\Questions\Manifests\QuestionsManifest;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;

class Manager implements ManagerInterface
{
    private string $currentQuestionSetVersion = "1";

    public function __construct(
        private ?ServerRequestInterface $request = null,
        private ?RepositoryInterface $repository = null
    ) {
        $this->request = $this->request ?? ServerRequestFactory::fromGlobals();
        $this->repository = $this->repository ?? new Repository();
    }

    public function retrieveSocialCompassQuestions(): array
    {
        $questionsList = $this->retrieveCurrentQuestionsSet();
        return $this->prepareSocialCompassQuestions($questionsList);
    }

    private function retrieveCurrentQuestionsSet(): QuestionsManifest
    {
        $manifest = "Minds\Core\SocialCompass\Questions\Manifests\QuestionsManifestV{$this->currentQuestionSetVersion}";
        return new $manifest();
    }

    private function prepareSocialCompassQuestions(QuestionsManifest $questionsList): array
    {
        $results = [];
        foreach ($questionsList::QUESTIONS as $questionClass) {
            $userGuid = Session::getLoggedInUserGuid();

            $question = new $questionClass();

            $answer = $this->repository->getAnswerByQuestionId($userGuid, $question->getQuestionId());
            if (isset($answer) && $answer->count() > 0) {
                $question->setCurrentValue($answer->getCurrentValue());
            }

            array_push($results, $question);
        }
        return $results;
    }

    public function storeSocialCompassAnswers(array $answers): bool
    {
        $userGuid = Session::getLoggedInUserGuid();

        return $this->repository->storeAnswers($userGuid, $answers);
    }

    public function updateSocialCompassAnswers(array $answers): bool
    {
        $userGuid = Session::getLoggedInUserGuid();

        return $this->repository->storeAnswers($userGuid, $answers);
    }
}
