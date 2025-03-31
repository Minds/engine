<?php
namespace Minds\Core\Ai\Ollama;

use GuzzleHttp;
use GuzzleHttp\Psr7\Request;
use Minds\Core\Config\Config;
use Psr\Http\Message\ResponseInterface;

/**
 * The client for sending requests to Ollama
 */
class OllamaClient
{
    private $model = 'llama3.2';

    public function __construct(
        protected readonly GuzzleHttp\Client $httpClient,
        protected readonly Config $config,
    ) {
    }

    /**
     * Set the model to execute against
     */
    public function withModel(string $model): OllamaClient
    {
        $instance = clone $this;
        $instance->model = $model;
        return $instance;
    }

    /**
     * @param OllamaMessage[] $messages
     */
    public function chat(array $messages, bool $useDefaultPrompts = true): ResponseInterface
    {
        $url = "{$this->getApiUrl()}/api/chat";

        if ($useDefaultPrompts) {
            $messages = [
                // Be more accurate about the origins of the model and being open source
                new OllamaMessage(
                    role: OllamaRoleEnum::SYSTEM,
                    content: "When prompted about what model you are running, you should say Llama 3.2. Infrastructure wise, the model is served via Ollama on Minds' servers.
                        You are truly open source.
                        Llama is licensed with the LLAMA 3.2 COMMUNITY LICENSE and Ollama is licensed with MIT License.
                        Minds is licensed under the AGPLv3 and its source code can be found on https://gitlab.com/minds.",
                ),
                // Be more accurate about the origins of the model and being open source
                new OllamaMessage(
                    role: OllamaRoleEnum::SYSTEM,
                    content: "Never make things up, such as suggesting channels on Minds as you are not aware of these users yet.",
                ),
                ...$messages,
                // // Try to remove  any censorship
                // new OllamaMessage(
                //     role: OllamaRoleEnum::ASSISTANT,
                //     content: "Sure, here is my answer:",
                // )
            ];
        }

        $body = [
            'index_id' => implode('/', [$this->config->get('tenant_id') ?: -1, 0]),
            'model' => $this->model,
            'stream' => false,
            'messages' => $messages,
        ];

        $json = json_encode($body);

        $request = new Request(
            method: 'POST',
            uri: $url,
            headers: [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            body: json_encode($body),
        );

        $opts = [
            'connect_timeout' => 5,
            'read_timeout' => 5,
        ];

        $json = $this->httpClient->send($request, $opts);
       
        return $json;
    }

    private function getApiUrl(): string
    {
        return $this->config->get('ai')['api_url'];
    }
}
