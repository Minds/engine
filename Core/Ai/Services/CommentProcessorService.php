<?php
namespace Minds\Core\Ai\Services;

use Minds\Core\Ai\Ollama\OllamaClient;
use Minds\Core\Ai\Ollama\OllamaMessage;
use Minds\Core\Ai\Ollama\OllamaRoleEnum;
use Minds\Core\Comments\Manager as CommentManager;

use Minds\Core\Comments\Comment;
use Minds\Core\Config\Config;
use Minds\Core\Entities\TaggedUsersService;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Security\ACL;
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
        private readonly ACL $acl,
    ) {
        
    }

    /**
     * When a comment is made, check if its on a post of, or a reply to, a bot account
     */
    public function onComment(Comment $comment): bool
    {
        if (!$comment->getBody()) {
            return true; // No message in body. Skip.
        }

        $this->acl->setIgnore(true);

        $entity = $this->entitiesBuilder->single($comment->getEntityGuid());

        if (!$entity instanceof Activity) {
            // If we're not commenting on an Activity post, we don't want to continue processing.
            $this->logger->info("Bad entity. Skipping.", [
                'comment_urn' => $comment->getUrn(),
            ]);
            return true;
        }
    
        $entityOwner = $this->entitiesBuilder->single($entity->getOwnerGuid());
        
        if (!$entityOwner instanceof User) {
            // If the entityOwner is invalid, we don't want to continue processing.
            $this->logger->info("Bad user. Skipping.", [
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

    /**
     * On an activity post, check for tags
     */
    public function onActivity(Activity $activity): bool
    {
        $activityOwner = $this->entitiesBuilder->single($activity->getOwnerGuid());

        if (!$activityOwner instanceof User) {
            return true; // Bad user, do not try to reprocess
        }

        $taggedUsers = $this->taggedUsersService->getUsersFromText($activity->getTitle() . ' ' . $activity->getMessage(), $activityOwner);

        foreach ($taggedUsers as $taggedUser) {
            if (!$this->onActivityTag($activity, $taggedUser)) {
                $this->logger->error("Failure: {$activity->getUrn()}. Could not save comment.");
                return false;
            }
        }

        return true;
    }

    /**
     * When a post is made, check if a bot has been tagged in it, and reply
     */
    protected function onActivityTag(Activity $activity, User $taggedUser): bool
    {
        if (!$taggedUser->isBot()) {
            $this->logger->info("The tagged user was not a bot. Skipping.", [
                'entity_urn' => $activity->getUrn(),
            ]);
            return true;
        }
    
        $activityOwner = $this->entitiesBuilder->single($activity->getOwnerGuid());

        if (!$activityOwner instanceof User) {
            $this->logger->info("Bad post owner. Skipping.", [
                'entity_urn' => $activity->getUrn(),
            ]);
            return true; // Bad user
        }

        $messages = [
            new OllamaMessage(
                role: OllamaRoleEnum::SYSTEM,
                content: "
                You have have been tagged in a social media post and will reply to the post.
                Your username is @{$taggedUser->getUsername()} and you are replying to @{$activityOwner->getUsername()}, but you don't need to mention them
                in your reply.
                "
            ),
            new OllamaMessage(
                role: OllamaRoleEnum::USER,
                content: "{$activity->getTitle()} {$activity->getMessage()}",
            )
        ];

        $botComment = new Comment();
        $botComment->setEntityGuid($activity->getGuid());
        $botComment->setParentGuidL1(0);
        $botComment->setParentGuidL2(0);

        $botComment->setBody($this->getBotAnswer($messages));
        $botComment->setOwnerGuid($taggedUser->getGuid());
        $botComment->setTimeCreated(time());

        $this->logger->info("Processed.", [
            'entity_urn' => $activity->getUrn(),
        ]);

        // Leave a new comment
        return $this->commentsManager->add($botComment);
    }

    private function getBotAnswer(array $messages): string
    {
        $response = $this->ollamaClient->chat($messages);

        $result = json_decode($response->getBody()->getContents(), true);

        return ltrim($result['message']['content']);
    }

}
