<?php
namespace Minds\Core\Comments\EmbeddedComments\Services;

use Exception;
use Minds\Core\Comments\Comment;
use Minds\Core\Comments\Manager;
use Minds\Core\Config\Config;
use Minds\Entities\Activity;
use Minds\Core\Log\Logger;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class EmbeddedCommentsCommentService
{
    public function __construct(
        private Manager $commentsManager,
        private Config $config,
        private ACL $acl,
        private Logger $logger,
    ) {
        
    }

    /**
     * Yields comments
     * @return iterable<Comment>
     */
    public function getComments(
        Activity $activity,
        string $parentPath = '0:0:0',
        int $limit = 12,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null,
        int &$totalCount = 0,
    ): iterable {
        // Get the total count
        $totalCount = $this->commentsManager->count($activity->getGuid(), $parentPath);

        // Find comments

        $response = $this->commentsManager->getList([
            'entity_guid' => $activity->getGuid(),
            'parent_path' => $parentPath,
            'limit' => $limit,
            'offset' => $loadAfter ?: null,
        ]);

        $comments = $response->toArray();

        $loadAfter = (string) $response->getPagingToken();

        $hasMore = !$response->isLastPage();

        yield from $comments;
    }

    /**
     * Creates a comment
     */
    public function createComment(Activity $activity, string $parentPath, User $owner, string $body): Comment
    {
        $parentGuids = explode(':', $parentPath);

        $comment = new Comment();
        $comment
            ->setEntityGuid($activity->getGuid())
            ->setParentGuidL1($parentGuids[0] ?? 0)
            ->setParentGuidL2($parentGuids[1] ?? 0)
            ->setOwnerObj($owner)
            ->setContainerGuid($owner->getGuid())
            ->setTimeCreated(time())
            ->setTimeUpdated(time())
            ->setClientMeta([])
            ->setBody($body);
        
        if (!$this->commentsManager->add($comment)) {
            throw new ServerErrorException("Could not save comment");
        }

        return $comment;
    }

}
