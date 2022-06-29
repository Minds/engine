<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\Di\Di;
use Minds\Core\SocialCompass\Delegates\ActionDelegateManager;
use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Core\SocialCompass\Questions\Manifests\QuestionsManifest;
use Minds\Entities\User;

class Manager implements ManagerInterface
{
    private string $currentQuestionSetVersion = "1";

    public function __construct(
        private ?RepositoryInterface $repository = null,
        private ?User $targetUser = null,
        private ?ActionDelegateManager $actionDelegateManager = null
    ) {
        $this->repository = $this->repository ?? new Repository();

        $this->targetUser = $this->targetUser ?? $this->getLoggedInUser();
        $this->actionDelegateManager = $this->actionDelegateManager ?? Di::_()->get('SocialCompass\Delegates\ActionDelegateManager');
    }

    private function getLoggedInUser(): ?User
    {
        $activeSession = Di::_()->get('Sessions\ActiveSession');
        return $activeSession->getUser();
    }

    public function setUser(User $user): self
    {
        $this->targetUser = $user;
        return $this;
    }

    /**
     * @return array
     *         [
     *             "questions": BaseQuestion[]
     *             "answersProvided": bool
     *         ]
     */
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

    /**
     * @param QuestionsManifest $questionsList
     * @return array
     *         [
     *             "questions": BaseQuestion[]
     *             "answersProvided": bool
     *         ]
     */
    private function prepareSocialCompassQuestions(QuestionsManifest $questionsList): array
    {
        $results = [
            "questions" => [],
            "answersProvided" => false
        ];
        $answers = [];
        if ($this->targetUser) {
            $answers = $this->repository->getAnswers($this->getUserId());
            $results["answersProvided"] = $answers && count($answers) > 0;
        }

        foreach ($questionsList::QUESTIONS as $questionClass) {
            $question = new $questionClass();

            if ($this->targetUser && !empty($answers[$question->getQuestionId()])) {
                $question->setCurrentValue(
                    $answers[$question->getQuestionId()]
                        ->getCurrentValue()
                );
            }

            array_push($results["questions"], $question);
        }
        return $results;
    }

    /**
     * @param AnswerModel[] $answers
     * @return bool
     */
    public function storeSocialCompassAnswers(array $answers): bool
    {
        $success = $this->repository->storeAnswers($answers);
    
        if ($success) {
            $this->actionDelegateManager->onAnswersProvided($answers);
        }

        return $success;
    }

    /**
     * @param AnswerModel[] $answers
     * @return bool
     */
    public function updateSocialCompassAnswers(array $answers): bool
    {
        $success = $this->repository->storeAnswers($answers);
        
        if ($success) {
            $this->actionDelegateManager->onAnswersProvided($answers);
        }

        return $success;
    }

    /**
     * Count answers for compass for instance $targetUser.
     * @return array - count of answers.
     */
    public function countAnswers(): int
    {
        return $this->repository->countAnswers(
            $this->getUserId()
        );
    }

    private function getUserId(): int
    {
        return (int) $this->targetUser?->getGuid();
    }
}
