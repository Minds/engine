<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\Di\Di;
use Minds\Core\Sessions\ActiveSession;
use Minds\Core\SocialCompass\Questions\Manifests\QuestionsManifest;
use Minds\Entities\User;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;

class Manager implements ManagerInterface
{
    private string $currentQuestionSetVersion = "1";
    private ?User $loggedInUser;

    public function __construct(
        private ?ServerRequestInterface $request = null,
        private ?RepositoryInterface $repository = null,
        ?ActiveSession $activeSession = null,
    ) {
        $this->request = $this->request ?? ServerRequestFactory::fromGlobals();
        $this->repository = $this->repository ?? new Repository();
        $activeSession = $activeSession ?? Di::_()->get('Sessions\ActiveSession');
        $this->loggedInUser = $activeSession?->getUser();
    }

    public function setUser(User $user): self
    {
        $this->loggedInUser = $user;
        return $this;
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
            $question = new $questionClass();

            if ($this->loggedInUser) {
                $answer = $this->repository->getAnswerByQuestionId($this->getUserId(), $question->getQuestionId());
                if (isset($answer) && $answer->count() > 0) {
                    $question->setCurrentValue($answer->getCurrentValue());
                }
            }

            array_push($results, $question);
        }
        return $results;
    }

    public function storeSocialCompassAnswers(array $answers): bool
    {
        if ($this->loggedInUser == null) {
            return false;
        }
        return $this->repository->storeAnswers($this->getUserId(), $answers);
    }

    public function updateSocialCompassAnswers(array $answers): bool
    {
        if ($this->loggedInUser == null) {
            return false;
        }
        return $this->repository->storeAnswers($this->getUserId(), $answers);
    }

    private function getUserId(): int
    {
        return (int) $this->loggedInUser?->getGuid();
    }
}
