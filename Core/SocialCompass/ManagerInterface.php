<?php
namespace Minds\Core\SocialCompass;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

interface ManagerInterface
{
    /**
     * Retrieves the active Social Compass question set and
     * if the user has answered them previously it also sets the answers to the relative questions
     * @return JsonResponse The built response containing the current Social Compass question set
     *                      and the relative answers if the loggedIn user has answered them previously
     */
    public function retrieveSocialCompassQuestions() : JsonResponse;

    /**
     * Stores the answers to the Social Compass questions set in the database
     * @return JsonResponse The successful response if the answers have been stored, an error response otherwise
     */
    public function storeSocialCompassAnswers() : JsonResponse;

    public function updateSocialCompassAnswers() : JsonResponse;
}
