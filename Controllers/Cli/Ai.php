<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Ai\Ollama\OllamaClient;
use Minds\Core\Ai\Ollama\OllamaMessage;
use Minds\Core\Ai\Ollama\OllamaRoleEnum;
use Minds\Core\Ai\Services\EntityIntelligenceService;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
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

    /**
     * Command line to create chats with all active users
     */
    public function createChats()
    {
        /** @var \GuzzleHttp\Client */
        $httpClient = Di::_()->get('PostHogHttpClient');
        /** @var EntitiesBuilder */
        $entitiesBuilder = Di::_()->get(EntitiesBuilder::class);
        /** @var RoomService */
        $chatRoomService = Di::_()->get(RoomService::class);
        /** @var MessageService */
        $chatMessagesService = Di::_()->get(MessageService::class);
        /** @var Config */
        $config = Di::_()->get(Config::class);

        /** @var User */
        $botUser = $entitiesBuilder->single($config->get('ai')['default_chat_user_guid']);

        $query = "SELECT person.properties.guid from events
                    WHERE timestamp > subtractDays(now(), 30)
                    AND person.properties.guid IS NOT NULL
                    GROUP BY person.properties.guid
                    ORDER BY person.properties.guid ASC
                    LIMIT 1000000";

        $response = $httpClient->post("api/projects/17449/query", [
            'json' => [
                'query' => [
                    'kind' => 'HogQLQuery',
                    'query' => $query,
                ]
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $results = $data['results'];

        foreach ($results as $row) {
            $guid = $row[0];

            $user = $entitiesBuilder->single($guid);

            if (!$user instanceof User) {
                $this->out("$guid not found");
                continue;
            }

            // Create a chat room, if one doesn't already exist#
            $roomEdge = $chatRoomService->createRoom(
                user: $botUser,
                otherMemberGuids: [
                    $user->getGuid(),
                ]
            );

            // Get chat history (if there is a chat history, skip)
            [ 'edges' => $messageEdges ] = $chatMessagesService->getMessages($roomEdge->getNode()->getGuid(), $botUser, 1);

            if (count($messageEdges) > 0) {
                $this->out("$guid already started chatting");
                continue;
            }

            // Send the message
            $message = "Hey @{$user->getUsername()}, I'm the Minds open-source and privacy-preserving AI assistant. Sometimes I'm a genius, but I often make mistakes so please check other sources. I will improve over time. Feel free to ask me anything.";
            $chatMessagesService->addMessage($roomEdge->getNode()->getGuid(), $botUser, $message);

            $this->out("$guid sent message");
        }
    }

    public function analyzeUser()
    {
        $username = $this->getOpt('username');

        /** @var EntityIntelligenceService */
        $service = Di::_()->get(EntityIntelligenceService::class);
        /** @var EntitiesBuilder */
        $entitiesBuilder = Di::_()->get(EntitiesBuilder::class);
        
        $user = $entitiesBuilder->getByUserByIndex($username);

        if (!$user instanceof User) {
            $this->out('User not found');
            return;
        }

        $service->analyzeUser($user);
    }
}
