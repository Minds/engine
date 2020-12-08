<?php

/**
 * Minds Comments Thread Notifications
 *
 * @author emi
 */

namespace Minds\Core\Comments\Delegates;

use Minds\Core\Comments\Comment;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Notification\PostSubscriptions\Manager;
use Minds\Core\Security\Block;
use Minds\Core\Comments\Repository;
use Minds\Core\Session;

class ThreadNotifications
{
    /** @var Manager */
    protected $postSubscriptionsManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Dispatcher */
    private $eventsDispatcher;

    /** @var Block\Manager */
    private $blockManager;

    /** @var Repository */
    protected $repository;

    /** @var  Logger **/
    private $logger;

    /**
     * ThreadNotifications constructor.
     * @param null $indexes
     */
    public function __construct($postSubscriptionsManager = null, $entitiesBuilder = null, $eventsDispatcher = null, $blockManager = null, $logger = null, $repository = null)
    {
        $this->postSubscriptionsManager = $postSubscriptionsManager ?: new Manager();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->blockManager = $blockManager ?: Di::_()->get('Security\Block\Manager');
        $this->logger = $logger ?: Di::_()->get('Logger');
        $this->repository = $repository ?: new Repository();
    }

    /**
     * Subscribes the Comment owner to the thread
     * @param Comment $comment
     */
    public function subscribeOwner(Comment $comment)
    {
        $this->postSubscriptionsManager
            ->setEntityGuid($comment->getEntityGuid())
            ->setUserGuid($comment->getOwnerGuid())
            ->follow(true);
    }

    /**
     * Notifies all thread subscribers about new comment
     * @param Comment $comment
     * @throws \Minds\Exceptions\StopEventException
     */
    public function notify(Comment $comment)
    {
        $isReply = $comment->getPartitionPath() !== '0:0:0';
        $subscribers = [];
    
        $entity = $this->entitiesBuilder->single($comment->getEntityGuid());
        if (!$entity || ($entity->type === 'group' && !$isReply)) {
            return;
        }

        if (!$isReply) { // only reply to owner
            $this->postSubscriptionsManager
                ->setEntityGuid($comment->getEntityGuid());

            $subscribers = $this->postSubscriptionsManager->getFollowers()
                ->filter(function ($userGuid) use ($comment) {
                    $blockEntry = new Block\BlockEntry();
                    $blockEntry->setActorGuid($comment->getOwnerGuid())
                        ->setSubjectGuid($userGuid);

                    // Exclude current comment creator
                    return $userGuid != $comment->getOwnerGuid()
                        && !$this->blockManager->hasBlocked($blockEntry);
                }, false)
                ->toArray();

            if (!$subscribers) {
                return;
            }
        } else {
            // TODO make a magic function here or something smarter (MH)
            $luid = $comment->getLuid();
            $parent_guids = explode(':', $luid->getPartitionPath());

            $parent_guid = "{$parent_guids[0]}";
            $parent_path = "0:0:0";
            if ($parent_guids[1] != 0) {
                $parent_guid = $parent_guids[1];
                $parent_path = $comment->getParentPath();
            }

            $luid->setPartitionPath($parent_path);
            $luid->setGuid($parent_guid);
            $parent = $this->entitiesBuilder->single($luid);
            if ($parent && $parent->getOwnerGuid() != $comment->getOwnerGuid()) {
                $subscribers = [ $parent->getOwnerGuid() ];
            }

            $otherChildren = $this->getChildOwnerGuids($parent, $comment->getOwnerGuid());

            // merge with all sibling ids
            $subscribers = array_unique(
                array_merge(
                    $subscribers,
                    $otherChildren,
                )
            );
        }

        $this->eventsDispatcher->trigger('notification', 'all', [
            'to' => $subscribers,
            'entity' => (string) $comment->getEntityGuid(),
            'description' => (string) $comment->getBody(),
            'params' => [
                'comment_guid' => (string) $comment->getGuid(),
                'parent_path' => (string) $comment->getPartitionPath(),
                'focusedCommentUrn' => $comment->getUrn(),
                'is_reply' => $comment->getPartitionPath() !== '0:0:0',
            ],
            'notification_view' => 'comment'
        ]);
    }

    /**
     * Gets array of owner_guids of children of a given parent excluding current user.
     * @param $parent - parent comment.
     * @param $commentAuthorGuid - original comment author.
     * @return array - array of owner_guids or empty array.
     */
    private function getChildOwnerGuids($parent, $commentAuthorGuid): array
    {
        try {
            $opts = [
                'entity_guid' => $parent->getEntityGuid(),
                'parent_path' => $parent->getChildPath(),
                'fields' => 'owner_guid',
                'scroll' => true,
            ];

            $scrollable = $this->repository->getList($opts);

            $this->postSubscriptionsManager->setEntityGuid(
                $parent->getEntityGuid()
            );

            $ownerGuids = [];

            foreach ($scrollable as $row) {
                if (
                    strval($commentAuthorGuid) === strval($row['owner_guid']) ||
                    in_array(strval($row['owner_guid']), $ownerGuids, true)
                ) {
                    continue;
                }

                $postSubscription = $this->postSubscriptionsManager
                    ->setUserGuid($row['owner_guid'])->get();

                if ($postSubscription->getFollowing()) {
                    array_push($ownerGuids, strval($row['owner_guid']));
                }
            }

            return $ownerGuids ?: [];
        } catch (\Exception $e) {
            $this->logger->error(
                $e->getMessage() ?:
                'An unknown error has occurred getting comment child GUIDs.'
            );
            return [];
        }
    }
}
