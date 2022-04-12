<?php

namespace Minds\Core\SocialCompass\Delegates;

/**
 * Abstract action delegate - for actions that happen in response
 * to social compass answer submissions.
 *
 * Implementation / Extension guidelines:
 *
 * Extend the class, populate the class level $controlQuestions
 * with the question names that are used to determine if there
 * should be action.
 *
 * Implement a handleScores function which will be passed a key value
 * array of QuestionName => Score, and the user guid. This function
 * should be used to handle the action.
 */
abstract class AbstractActionDelegate
{
    // Array of question names used to control the action.
    protected $controlQuestions = [];

    /**
     * To be called when answers are provided. Parses scores for
     * controlQuestions and passes to handleScore function.
     * @param array $answers - array of answer models - will be filtered
     * to leave only control questions.
     * @return void
     */
    public function onAnswersProvided(array $answers): void
    {
        $scores = $this->getControlQuestionScores($answers);
        $this->handleScores($scores, $answers[0]->getUserGuid());
    }

    /**
     * Gets scores for questions specified as control questions.
     * @param array $answers - AnswerModel array to be filtered.
     * @return array key value array - QuestionName => Score.
     */
    protected function getControlQuestionScores(array $answers): array
    {
        $scores = [];

        foreach ($answers as $answer) {
            $questionId = $answer->getQuestionId();
            if (in_array($questionId, $this->controlQuestions, true)) {
                $scores[$questionId] = $answer->getCurrentValue();
            }
        }

        return $scores;
    }

    /**
     * Abstract function to be implemented by extending classes that is to
     * handles the scores passed in and define the resulting action based on
     * score values.
     * @param array $scores - key value array of questions specified
     * as controlQuestions - QuestionName => Score.
     * @param string $userGuid - guid of the user.
     * @return void
     */
    abstract protected function handleScores(array $scores, string $userGuid): void;
}
