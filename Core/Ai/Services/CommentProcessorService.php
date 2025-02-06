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
use Minds\Core\Comments\Manager as CommentManager;

use Minds\Core\Comments\Comment;
use Minds\Core\Config\Config;
use Minds\Core\Entities\TaggedUsersService;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\User;

class CommentProcessorService
{
    public function __construct(
        private readonly OllamaClient $ollamaClient,
        private readonly CommentManager $commentsManager,
        private readonly Config $config,
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly TaggedUsersService $taggedUsersService,
        private readonly Logger $logger,
    ) {
        
    }

    /**
     * When a comment is made, check if its on a post of, or a reply to, a bot account
     */
    public function onComment(Comment $comment): bool
    {
        $entity = $this->entitiesBuilder->single($comment->getEntityGuid());
        $entityOwner = $this->entitiesBuilder->single($entity->getOwnerGuid());
        
        if (!$entity instanceof Activity || !$entityOwner instanceof User) {
            // If we're not commenting on an Activity post, we don't want to continue processing.
            // If the entityOwner is invalid, we don't want to continue processing.
            $this->logger->info("Bad entity or bad user. Skipping.", [
                'comment_urn' => $comment->getUrn(),
            ]);
            return true;
        }

        $commentOwner = $this->entitiesBuilder->single($comment->getOwnerGuid());
        if (!$commentOwner instanceof User || $commentOwner->isBot()) {
            $this->logger->info("The comment owner is a bot or is invalid. Skipping.", [
                'comment_urn' => $comment->getUrn(),
            ]);
            return true; // If the user not valid or its a bot, do not process.
        }

        $botUser = null;

        if ($entityOwner->isBot()) {
            // If a bot owns the post, we will reply to comment as the post owner
            $botUser = $entityOwner;
        } else {
            
            // If the post being commented on is not owned by a bot, see if a bot has been tagged in it
            $taggedUsers = $this->taggedUsersService->getUsersFromText($comment->getBody(), $commentOwner);

            foreach ($taggedUsers as $taggedUser) {
                if ($taggedUser->isBot()) {
                    // We have found a bot user
                    $botUser = $taggedUser;
                    continue;
                }
            }
        }

        if ((int) $comment->getParentGuidL1() === 0) {
            if (!$botUser) {
                $this->logger->info("No bots was tagged and/or owns the post. Skipping.", [
                    'comment_urn' => $comment->getUrn(),
                ]);
                return true; // No bot user has been found
            }
    
            $messages = [
                new OllamaMessage(
                    role: OllamaRoleEnum::SYSTEM,
                    content: "
                    You have created a social media post that a user is commenting on.
                    Your username is @{$botUser->getUsername()} and you are replying to @{$commentOwner->getUsername()}, but you don't need to mention them
                    in your reply.
                    The post has the following content:
                    {$entity->getTitle()} {$entity->getMessage()}
                    "
                ),
                new OllamaMessage(
                    role: OllamaRoleEnum::USER,
                    content: $comment->getBody(),
                )
            ];

            $botComment = new Comment();
            $botComment->setEntityGuid($entity->getGuid());
            $parentGuids = explode(':', $comment->getChildPath());
            $botComment->setParentGuidL1($parentGuids[0]);
            $botComment->setParentGuidL2($parentGuids[1]);

            $botComment->setBody($this->getBotAnswer($messages));
            $botComment->setOwnerGuid($botUser->getGuid());
            $botComment->setTimeCreated(time());

            $this->logger->info("Processed.", [
                'comment_urn' => $comment->getUrn(),
            ]);

            // Leave a new comment
            return $this->commentsManager->add($botComment);
        } else {

            $parentComment = $this->commentsManager->get($entity->getGuid(), $comment->getParentPath(), $comment->getParentGuid());
            if (!$parentComment instanceof Comment) {
                $this->logger->info("Bad parent comment. Skipping.", [
                    'comment_urn' => $comment->getUrn(),
                ]);
                return true; // Bad comment
            }
    
            $parentCommentOwner = $this->entitiesBuilder->single($parentComment->getOwnerGuid());
            if (!$parentCommentOwner instanceof User) {
                $this->logger->info("Bad parent comment owner. Skipping.", [
                    'comment_urn' => $comment->getUrn(),
                ]);
                return true; // Bad user
            }

            if ($parentCommentOwner->isBot()) {
                $botUser = $parentCommentOwner;
            } elseif (!$botUser) {
                $this->logger->info("Neither the parent comment owner, any tagged users or the post owner as bots. Skipping.", [
                    'comment_urn' => $comment->getUrn(),
                ]);
                return true; // Neither the parent comment owner, any tagged users or the post owner as bots
            }

            $systemPrompt = "";

            if ($entityOwner->getGuid() === $parentCommentOwner->getGuid()) {
                // If the same bot we are replying to also made the post, tell the system prompt
                $systemPrompt = "You have created a social media prompt that users are commenting on. Someone replied to a comment you made on your own post, and you are replying to them";
            } else {
                $systemPrompt = "You previously made a comment on some elses post. Somebody replied to a comment you made and you are replying to them";
            }

            $systemPrompt .= "
                Your username is @{$botUser->getUsername()} and you are replying to @{$commentOwner->getUsername()}, but you don't need to mention them
                in your reply.
                The post has the following content:
                    {$entity->getTitle()} {$entity->getMessage()}.
                ";

            $commentThread = $this->commentsManager->getList([
                'entity_guid' => $entity->getGuid(),
                'parent_path' => $comment->getChildPath(),
                'limit' => 24,
                'offset' => 0,
            ]);

            $messages = [
                new OllamaMessage(
                    role: OllamaRoleEnum::SYSTEM,
                    content: $systemPrompt
                ),
                new OllamaMessage(
                    role: OllamaRoleEnum::ASSISTANT,
                    content: $parentComment->getBody(),
                ),
                ...array_map(fn (Comment $comment) => new OllamaMessage(
                    role: $comment->getOwnerGuid() === $parentCommentOwner->getGuid() ? OllamaRoleEnum::ASSISTANT : OllamaRoleEnum::USER,
                    content: $comment->getBody(),
                ), array_filter($commentThread->toArray(), fn (Comment $a) => $a->getGuid() !== $comment->getGuid())),
                new OllamaMessage(
                    role: OllamaRoleEnum::USER,
                    content: $comment->getBody(),
                )
            ];

            $botComment = new Comment();
            $botComment->setEntityGuid($entity->getGuid());
            $parentGuids = explode(':', $comment->getChildPath());
            $botComment->setParentGuidL1($parentGuids[0]);
            $botComment->setParentGuidL2($parentGuids[1]);

            $botComment->setBody('@' . $commentOwner->getUsername() . ' ' . $this->getBotAnswer($messages));
            $botComment->setOwnerGuid($botUser->getGuid());
            $botComment->setTimeCreated(time());

            $this->logger->info("Processed.", [
                'comment_urn' => $comment->getUrn(),
            ]);

            // Leave a new comment
            return $this->commentsManager->add($botComment);
        }

        return false;
    }

    private function getBotAnswer(array $messages): string
    {
        $response = $this->ollamaClient->chat($messages);

        $result = json_decode($response->getBody()->getContents(), true);

        return $result['message']['content'];
    }

}
