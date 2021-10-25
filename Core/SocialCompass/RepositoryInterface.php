<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\SocialCompass\Entities\AnswerModel;

interface RepositoryInterface
{
    /**
     * Finds and returns the answers to the Social Compass questions
     * provided by a specific user.
     * @param int $userGuid The unique identifier of the user to fetch the answers for
     * @return array|null|false The list of answers found in the database or null if nothing has been found
     */
    public function getAnswers(int $userGuid, ?int $version = null) : iterable|null|false;

    public function getAnswerByQuestionId(int $userGuid, string $questionId) : AnswerModel|null|false;

    /**
     * Stores the answers the user has given to the Social Compass questions
     * @param int $userGuid The unique identifier of the user to use for storing the answers
     * @param array $answers The list of answers
     * @return bool
     */
    public function storeAnswers(int $userGuid, array $answers) : bool;

    public function updateAnswers(int $userGuid, array $answers) : bool;
}
