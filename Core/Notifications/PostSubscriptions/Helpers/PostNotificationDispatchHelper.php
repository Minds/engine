<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\PostSubscriptions\Helpers;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager as GroupsMembershipManager;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\PostSubscriptions\Helpers\Interfaces\PostNotificationDispatchHelperInterface;
use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use Minds\Entities\Entity;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

/**
 * Helper class for the post notification dispatch process.
 */
class PostNotificationDispatchHelper implements PostNotificationDispatchHelperInterface
{
    public function __construct(
        private GroupsMembershipManager $groupsMembershipManager,
        private EntitiesBuilder $entitiesBuilder,
        private Logger $logger,
    ) {
    }

    /**
     * Whether the post notification can be dispatched.
     * @param PostSubscription $postSubscription - post subscription to check.
     * @param Entity $forActivity - subject entity to check.
     * @return bool true if notification should be sent.
     */
    public function canDispatch(PostSubscription $postSubscription, Entity $forActivity): bool
    {
        $containerEntity = null;

        if ($forActivity->getContainerGuid()) {
            $containerEntity = $this->entitiesBuilder->single($forActivity->getContainerGuid());
        }

        if ($containerEntity && $containerEntity instanceof Group) {
            return $this->canDispatchForGroupContainer($postSubscription, $containerEntity);
        }

        return true;
    }

    /**
     * Whether notification can be dispatched for an entity in a group container;
     * if a post is created in a group that the notification reciever is not a member of,
     * then they should NOT receive a notification.
     * @param PostSubscription $postSubscription - post subscription to check.
     * @param Group $group - container group the post is being made to.
     * @return bool true if notification should be dispatched.
     */
    private function canDispatchForGroupContainer(PostSubscription $postSubscription, Group $group): bool
    {
        $recipient = $this->entitiesBuilder->single($postSubscription->userGuid);

        if (!($recipient instanceof User)) {
            return false;
        }

        try {
            $membership = $this->groupsMembershipManager->getMembership($group, $recipient);

            if (!$membership->isMember()) {
                return false;
            }
        } catch(NotFoundException $e) {
            $this->logger->info("Skipping for user {$postSubscription->userGuid} as they are not a member of this group");
            return false;
        }

        return true;
    }
}
