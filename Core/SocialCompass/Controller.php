<?php

namespace Minds\Core\SocialCompass;

use NotImplementedException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?ManagerInterface $manager = null
    ) {
        $this->manager = $this->manager ?? new Manager();
    }

    public function getQuestions(ServerRequestInterface $request) : JsonResponse
    {
        return $this->manager->retrieveSocialCompassQuestions();
    }

    public function storeAnswers(ServerRequestInterface $request) : JsonResponse
    {
        return $this->manager->storeSocialCompassAnswers();
    }

    public function updateAnswers(ServerRequestInterface $request) : JsonResponse
    {
        return $this->manager->updateSocialCompassAnswers();
    }
}
