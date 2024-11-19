<?php
declare(strict_types=1);

namespace Minds\Core\Comments\GraphQL\Controllers;

use Minds\Core\Comments\Manager;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Controller for managing pinned comments.
 */
class PinnedCommentsController
{
    public function __construct(
        private readonly Manager $manager,
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly Logger $logger
    ) {
    }

    /**
     * Pins or unpins a comment.
     * @param string $commentUrn - the URN of the comment to pin or unpin.
     * @param bool $pinned - whether to pin or unpin the comment.
     * @return bool - true if the comment was pinned or unpinned, false otherwise.
     */
    #[Mutation]
    #[Logged]
    public function commentPinnedState(
        string $commentUrn,
        bool $pinned,
        #[InjectUser] ?User $loggedInUser = null,
    ): bool {
        try {
            $comment = $this->manager->getByUrn($commentUrn);

            if (!$comment) {
                throw new GraphQLException('Comment not found');
            }

            if ($comment->getParentGuid()) {
                throw new GraphQLException('Only top level comments can be pinned');
            }

            $parentEntity = $this->entitiesBuilder->single(
                $comment->getEntityGuid()
            );

            if (!$parentEntity) {
                throw new GraphQLException('Parent entity not found');
            }

            if ($loggedInUser->getGuid() !== $parentEntity->getOwnerGuid()) {
                throw new GraphQLException('User is not the parent entity owner');
            }

            $comment->setPinned($pinned);
            $comment->markAsDirty('pinned');
            $this->manager->update($comment);

            return true;
        } catch (\Exception $e) {
            $this->logger->error($e);
            throw new GraphQLException($e->getMessage());
        }
    }

}
