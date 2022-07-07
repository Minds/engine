<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Entities\User;

/**
 * The interface defining the methods to implement for the Social Compass module manager
 */
interface ManagerInterface
{
    /**
     * Retrieves the Social Compass questions set
     * @return array
     *         [
     *             "questions": BaseQuestion[]
     *             "answersProvided": bool
     *         ]
     */
    public function retrieveSocialCompassQuestions(): array;

    /**
     * Set the user to be used for the other methods
     * @param User $user
     * @return $this
     */
    public function setUser(User $user): self;

    /**
     * Stores the answers to the Social Compass questions set in the database
     * @param AnswerModel[] $answers The list of Social Compass answers to store from the request object
     * @return bool True if the answers have successfully been stored, false otherwise
     */
    public function storeSocialCompassAnswers(array $answers): bool;

    /**
     * Updates the answers to the Social Compass questions set in the database
     * @param AnswerModel[] $answers The list of Social Compass answers to store from the request object
     * @return bool True if the answers have successfully been stored, false otherwise
     */
    public function updateSocialCompassAnswers(array $answers): bool;

    /**
     * Count answers for compass for instance $targetUser.
     * @return array - count of answers.
     */
    public function countAnswers(): int;
}
