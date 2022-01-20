<?php

namespace Minds\Core\AccountQuality;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The controller for the Account Quality module
 */
class Controller
{
    public function __construct(
        private ?ManagerInterface $manager = null
    ) {
        $this->manager = $this->manager ?? new Manager();
    }

    public function getAccountQualityScores(ServerRequestInterface $request): JsonResponse
    {

    }

    public function getAccountQualityScore(ServerRequestInterface $request): JsonResponse
    {
        $targetUserId = $this->
    }
}
