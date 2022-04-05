<?php

namespace Minds\Core\Notifications\Push\System;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 *
 */
class Controller
{
    public function schedule(ServerRequestInterface $request): JsonResponse
    {
    }

    public function getHistory(ServerRequestInterface $request): JsonResponse
    {
    }
}
