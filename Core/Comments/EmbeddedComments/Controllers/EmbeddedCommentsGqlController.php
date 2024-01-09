<?php
namespace Minds\Core\Comments\EmbeddedComments\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\Comments\Comment;
use Minds\Core\Comments\EmbeddedComments\Models\EmbeddedCommentsSettings;
use Minds\Core\Comments\EmbeddedComments\Types\EmbeddedCommentsConnection;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsActivityService;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsCommentService;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsSettingsService;
use Minds\Core\Comments\GraphQL\Types\CommentEdge;
use Minds\Core\Comments\GraphQL\Types\CommentNode;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class EmbeddedCommentsGqlController
{
    public function __construct(
        private readonly EmbeddedCommentsActivityService $embeddedCommentsActivityService,
        private readonly EmbeddedCommentsCommentService $embeddedCommentsCommentService,
        private readonly EmbeddedCommentsSettingsService $embeddedCommentsSettingsService,
    ) {
        
    }
    /**
     * Returns comments to be shown in the embedded comments app.
     * The comments will be associated with an activity post. If the activity post
     * does not exist, we will attempt to create it
     */
    #[Query]
    public function getEmbeddedComments(
        string $ownerGuid,
        string $url,
        string $parentPath = '0:0:0',
        ?int $first = null,
        ?string $after = null,
        ?int $last = null,
        ?string $before = null
    ): EmbeddedCommentsConnection {
        $edges = [];

        if ($first && $last) {
            throw new UserError("first and last supplied, can only paginate in one direction");
        }

        if ($after && $before) {
            throw new UserError("after and before supplied, can only provide one cursor");
        }

        if (!$first && !$last) {
            $first = 12;
        }

        $loadAfter = $after;
        $loadBefore = $before;
        $hasMore = false;
        $totalCount = 0;

        // Find (or create) the activity post
        $activity = $this->embeddedCommentsActivityService
            ->withOwnerGuid($ownerGuid)
            ->withUrl($url)
            ->getActivityFromUrl(import: true);

        // Load the comments
        foreach ($this->embeddedCommentsCommentService->getComments(
            activity: $activity,
            parentPath: $parentPath,
            limit: $first,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
            hasMore: $hasMore,
            totalCount: $totalCount,
        ) as $comment) {
            $edges[] = new CommentEdge($comment, "");
        }

        $connection = new EmbeddedCommentsConnection();
        $connection->setTotalCount($totalCount);
        $connection->setActivityUrl($activity->getURL());
        $connection->setEdges($edges);
        $connection->setPageInfo(new PageInfo(
            hasNextPage: $hasMore,
            hasPreviousPage: $after && $loadBefore,
            startCursor: $loadAfter,
            endCursor: $loadBefore,
        ));

        return $connection;
    }

    /**
     * Creates a comment on a remote url
     */
    #[Mutation]
    public function createEmbeddedComment(
        string $ownerGuid,
        string $url,
        string $parentPath,
        string $body,
        #[InjectUser] ?User $loggedInUser = null,
    ): CommentEdge {
        // Find the activity post
        $activity = $this->embeddedCommentsActivityService
            ->withOwnerGuid($ownerGuid)
            ->withUrl($url)
            ->getActivityFromUrl(import: false);

        if (!$activity->getAllowComments()) {
            throw new ForbiddenException("Comments are disabled for this post");
        }

        $comment = $this->embeddedCommentsCommentService->createComment(
            activity: $activity,
            parentPath: $parentPath,
            owner: $loggedInUser,
            body: $body,
        );

        return new CommentEdge($comment, "");
    }

    /**
     * Returns the configured embedded-comments plugin settings for a user
     */
    #[Query]
    public function getEmbeddedCommentsSettings(
        #[InjectUser] ?User $loggedInUser = null,
    ): ?EmbeddedCommentsSettings {
        return $this->embeddedCommentsSettingsService->getSettings($loggedInUser->getGuid(), useCache: false);
    }

    /**
     * Creates a comment on a remote url
     */
    #[Mutation]
    #[Logged]
    public function setEmbeddedCommentsSettings(
        string $domain,
        string $pathRegex,
        bool $autoImportsEnabled,
        #[InjectUser] ?User $loggedInUser = null,
    ): EmbeddedCommentsSettings {
        $settings = new EmbeddedCommentsSettings(
            userGuid: $loggedInUser->guid,
            domain: $domain,
            pathRegex: $pathRegex,
            autoImportsEnabled: $autoImportsEnabled,
        );

        $this->embeddedCommentsSettingsService->setSettings($settings);

        return $settings;
    }
}
