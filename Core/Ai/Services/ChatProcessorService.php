<?php
namespace Minds\Core\Ai\Services;

use Minds\Core\Ai\Ollama\OllamaClient;
use Minds\Core\Ai\Ollama\OllamaMessage;
use Minds\Core\Ai\Ollama\OllamaRoleEnum;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Services\ChatImageStorageService;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Entities\User;

class ChatProcessorService
{
    public function __construct(
        private readonly OllamaClient $ollamaClient,
        private readonly MessageService $chatMessageService,
        private readonly RoomService $chatRoomService,
        private readonly ChatImageStorageService $chatImageStorageService,
        private readonly Config $config,
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly Logger $logger,
    ) {
        
    }

    /**
     * When a message is received, process it and determine if a bot user should respond
     */
    public function onMessage(ChatMessage $message): bool
    {
        /** @var User */
        $senderUser = $this->entitiesBuilder->single($message->senderGuid);

        if (!$senderUser instanceof User) {
            $this->logger->info("Skipping. Bad sender.", [ 'urn' => $message->getUrn() ]);
            return true;
        }

        // Determine if this chat room is a chat with a bot
        $botUser = $this->getBotUserFromRoomGuid(roomGuid: $message->roomGuid, sender: $senderUser);

        // If a bad user returned OR the sender is the bot, cancel out.
        if (!$botUser instanceof User || $message->senderGuid === (int) $botUser->getGuid()) {
            $this->logger->info("Skipping. Bad user or a bot user.", [ 'urn' => $message->getUrn() ]);
            return true; // Successfully processed, do not attempt to retry
        }

        // Get a list of previous message
        $chatHistoryEdges = $this->chatMessageService->getMessages(
            roomGuid: $message->roomGuid,
            user: $botUser,
        );

        // if (count($chatHistoryEdges['edges']) > 10 && !$senderUser->isPlus() && !$this->config->get('tenant_id')) {
        //     $this->chatMessageService->addMessage(
        //         roomGuid: $message->roomGuid,
        //         user: $botUser,
        //         message: "Hey... please can you upgrade to Plus in order to continue chatting with me? https://www.minds.com/plus.",
        //     );
        //     return true; // User is not plus
        // }

        $images = [];

        if ($message->image) {
            $images = [
                base64_encode(
                    $this->chatImageStorageService->downloadToMemory(
                        imageGuid: $message->image->guid,
                        ownerGuid: $message->getOwnerGuid()
                    )
                )
            ];
        }

        $response = $this->ollamaClient->chat([
            // The system message informs the bot how to behave
            new OllamaMessage(
                role: OllamaRoleEnum::SYSTEM,
                content: "You are an open source bot who is chatting with a user on the social media site " . $this->config->get('site_url') . "
                    Give short and concise answers where appropriate.
                    You are responding to {$senderUser->getName()} with a username of @{$senderUser->getUsername()}.
                ",
            ),
            // Get the last X messages so the assistant has context as to their chat
            ... array_filter(array_map(function (ChatMessageEdge $edge) use ($botUser) {
                return new OllamaMessage(
                    role: $botUser->getGuid() === $edge->getNode()->sender->getNode()->getGuid() ? OllamaRoleEnum::ASSISTANT : OllamaRoleEnum::USER,
                    content: $edge->getNode()->getPlainText(),
                );
            }, $chatHistoryEdges['edges']), fn (OllamaMessage $msg) => $msg->content !== $message->plainText),
            // Share the last message the user made.
            new OllamaMessage(
                role: OllamaRoleEnum::USER,
                content: $images ? 'What is this image?' : $message->plainText,
                images: $images,
            ),
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        $success = !!$this->chatMessageService->addMessage(
            roomGuid: $message->roomGuid,
            user: $botUser,
            message: ltrim($result['message']['content'], ' '),
        );

        $this->logger->info("Replying", [ 'urn' => $message->getUrn(), 'message' => $result['message']['content'] ]);

        return $success;
    }

    /**
     * If there is a bot user in a room, return that user.
     * Note: Only one bot user is currrently supported at this time
     */
    private function getBotUserFromRoomGuid(int $roomGuid, User $sender): ?User
    {
        [ 'edges' => $edges ] = $this->chatRoomService->getRoomMembers(roomGuid: $roomGuid, loggedInUser: $sender, excludeSelf: true);
        $users = array_map(fn ($edge) => $edge->getNode()->getUser(), $edges);
        foreach ($users as $user) {
            // Check if this user is a bot
            if ($user->isBot()) {
                return $user;
            }
        }

        return null;
    }
}
