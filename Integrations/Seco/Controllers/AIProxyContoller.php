<?php
namespace Minds\Integrations\Seco\Controllers;

use Minds\Core\Ai\Ollama\OllamaClient;
use Minds\Core\Ai\Ollama\OllamaMessage;
use Minds\Core\Ai\Ollama\OllamaRoleEnum;
use Minds\Core\EntitiesBuilder;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class AIProxyContoller
{
    public function __construct(
        private OllamaClient $ollamaClient,
        private EntitiesBuilder $entitiesBuilder,
    ) {
        
    }

    public function chat(ServerRequest $request): JsonResponse
    {
        $messages = array_map(fn ($message) => new OllamaMessage(
            role: constant(OllamaRoleEnum::class . '::' . $message['role']),
            content: $message['content']
        ), $request->getParsedBody()['messages']);

        $response = $this->ollamaClient->chat($messages, false);

        $json = $response->getBody()->getContents();

        return new JsonResponse(json_decode($json, true));
    }
}
