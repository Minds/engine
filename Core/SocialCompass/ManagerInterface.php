<?php
namespace Minds\Core\SocialCompass;

use Zend\Diactoros\Response\JsonResponse;

interface ManagerInterface
{
    /**
     * Retrieves the Social Compass questions set and
     * @return array
     */
    public function retrieveSocialCompassQuestions(): array;

    /**
     * Stores the answers to the Social Compass questions set in the database
     * @param array $answers The list of Social Compass answers to store from the request object
     * @return bool True if the answers have successfully been stored, false otherwise
     */
    public function storeSocialCompassAnswers(array $answers) : bool;

    /**
     * Updates the answers to the Social Compass questions set in the database
     * @param array $answers The list of Social Compass answers to store from the request object
     * @return bool True if the answers have successfully been stored, false otherwise
     */
    public function updateSocialCompassAnswers(array $answers) : bool;
}
