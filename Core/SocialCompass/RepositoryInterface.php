<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Core\SocialCompass\Questions\BaseQuestion;
use Minds\Exceptions\ServerErrorException;

interface RepositoryInterface
{
    /**
     * Finds and returns the answers to the Social Compass questions
     * provided by a specific user.
     * @param int $userGuid The unique identifier of the user to fetch the answers for
     * @return AnswerModel[] The list of answers found in the database
     * @throws ServerErrorException
     */
    public function getAnswers(int $userGuid): array;


    /**
     * Count answers to Social Compass questions provided by a specific user.
     * @param int $userGuid - guid of target user.
     * @return int - count of answers.
     * @throws ServerErrorException
     */
    public function countAnswers(int $userGuid): int;

    /**
     * Returns the answer object for a specific questionId if it exists
     * @param int $userGuid
     * @param string $questionId
     * @return AnswerModel
     * @throws ServerErrorException
     */
    public function getAnswerByQuestionId(int $userGuid, string $questionId): AnswerModel;

    /**
     * Stores the answers the user has given to the Social Compass questions
     * @param AnswerModel[] $answers The list of answers
     * @return bool
     */
    public function storeAnswers(array $answers): bool;

    /**
     * Updates the answers the user has given to the Social Compass questions
     * @param AnswerModel[] $answers The list of answers
     * @return bool
     */
    public function updateAnswers(array $answers): bool;
}
