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
    ) {
        
    }

    /**
     * When a message is received, process it and determine if a bot user should respond
     */
    public function onMessage(ChatMessage $message): bool
    {
        /** @var User */
        $senderUser = $this->entitiesBuilder->single($message->senderGuid);

        // Determine if this chat room is a chat with a bot
        $botUser = $this->getBotUserFromRoomGuid(roomGuid: $message->roomGuid, sender: $senderUser);

        // If a bad user returned OR the sender is the bot, cancel out.
        if (!$botUser instanceof User || $message->senderGuid === (int) $botUser->getGuid()) {
            return true; // Successfully processed, do not attempt to retry
        }

        // Get a list of previous message
        $chatHistoryEdges = $this->chatMessageService->getMessages(
            roomGuid: $message->roomGuid,
            user: $botUser,
        );

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
                content: "You an open source bot who is chatting with a user on the social media site " . $this->config->get('site_url') . ". Give short an concise answers where appropriate.",
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
            )
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return !!$this->chatMessageService->addMessage(
            roomGuid: $message->roomGuid,
            user: $botUser,
            message: $result['message']['content']
        );
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
