<?php

namespace Minds\Core\SocialCompass;

use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Core\SocialCompass\Questions\Manifests\QuestionsManifest;
use Minds\Entities\User;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;

class Manager implements ManagerInterface
{
    private string $currentQuestionSetVersion = "1";

    public function __construct(
        private ?ServerRequestInterface $request = null,
        private ?RepositoryInterface $repository = null,
        private ?User $targetUser = null
    ) {
        $this->request = $this->request ?? ServerRequestFactory::fromGlobals();
        $this->repository = $this->repository ?? new Repository();

        $this->targetUser = $this->targetUser ?? $this->getLoggedInUser();
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
            $results["answersProvided"] = count($answers) > 0;
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
        if ($this->targetUser == null) {
            return false;
        }
        return $this->repository->storeAnswers($answers);
    }

    /**
     * @param AnswerModel[] $answers
     * @return bool
     */
    public function updateSocialCompassAnswers(array $answers): bool
    {
        if ($this->targetUser == null) {
            return false;
        }
        return $this->repository->storeAnswers($answers);
    }

    private function getUserId(): int
    {
        return (int) $this->targetUser?->getGuid();
    }
}
