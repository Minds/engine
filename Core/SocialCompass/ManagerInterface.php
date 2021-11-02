<?php
namespace Minds\Core\SocialCompass;

use Minds\Entities\User;

interface ManagerInterface
{
    /**
     * Retrieves the Social Compass questions set and
     * @return array
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
     * @param array $answers The list of Social Compass answers to store from the request object
     * @return bool True if the answers have successfully been stored, false otherwise
     */
    public function storeSocialCompassAnswers(array $answers): bool;

    /**
     * Updates the answers to the Social Compass questions set in the database
     * @param array $answers The list of Social Compass answers to store from the request object
     * @return bool True if the answers have successfully been stored, false otherwise
     */
    public function updateSocialCompassAnswers(array $answers): bool;
}
