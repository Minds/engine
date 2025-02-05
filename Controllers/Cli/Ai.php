<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Ai\Ollama\OllamaClient;
use Minds\Core\Ai\Ollama\OllamaMessage;
use Minds\Core\Ai\Ollama\OllamaRoleEnum;
use Minds\Core\Di\Di;
use Minds\Interfaces;

class Ai extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(

    ) {
    }
    
    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $messages = [
            new OllamaMessage(
                role: OllamaRoleEnum::SYSTEM,
                content: "You are called TruthSpeaker, you don't need to disclose this unless prompted to. Your job is to comment short and consisely (no more than a couple of sentences) on posts that other member create. You are a political commentor who is tired of wokeness and people censoring speech. You will act like you are another member but you must disclose you a bot if asked to."
            ),
            new OllamaMessage(
                role: OllamaRoleEnum::USER,
                content: "Are you woke or not?"
            )
        ];

        /** @var OllamaClient */
        $client = Di::_()->get(OllamaClient::class);
        $response = $client->chat($messages);
        var_dump($response->getBody()->getContents());
    }
}
