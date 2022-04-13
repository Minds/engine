<?php

namespace Minds\Core\Notifications\Push\System;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 *
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
    }

    public function schedule(ServerRequestInterface $request): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success'
        ]);
    }

    public function getHistory(ServerRequestInterface $request): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'notifications' => [
                [
                    'title' => "Sample Title 1",
                    'message' => "Sample Message 1",
                    'link' => "Sample Link 1",
                    'timestamp' => strtotime("now")*1000,
                ],
                [
                    'title' => "Sample Title 2",
                    'message' => "Sample Message 2",
                    'link' => "Sample Link 2",
                    'timestamp' => strtotime("now")*1000,
                ],
                [
                    'title' => "Sample Title 3",
                    'message' => "Sample Message 3",
                    'link' => "Sample Link 3",
                    'timestamp' => strtotime("now")*1000,
                ],
            ]
        ]);
    }
}
