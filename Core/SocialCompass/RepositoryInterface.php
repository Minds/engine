<?php

namespace Minds\Core\SocialCompass;

interface RepositoryInterface
{
    /**
     * Finds and returns the answers to the Social Compass questions
     * provided by a specific user.
     * @param int $userGuid The unique identifier of the user to fetch the answers for
     * @return array|null The list of answers found in the database or null if nothing has been found
     */
    function getAnswers(int $userGuid, ?int $version = null) : array|null;

    /**
     * Stores the answers the user has given to the Social Compass questions
     * @param int $userGuid The unique identifier of the user to use for storing the answers
     * @param array $answers The list of answers
     * @return bool
     */
    function storeAnswers(int $userGuid, array $answers) : bool;

    function updateAnswers(int $userGuid, array $answers) : bool;
}
