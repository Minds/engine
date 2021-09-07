<?php
/**
 * This subscription will build notifications from stream events
 */
namespace Minds\Core\Comments;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications;
use Minds\Entities\User;
use Minds\Core\Security\Block;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notification\PostSubscriptions;
use Minds\Entities\EntityInterface;

class CommentNotificationsEventStreamsSubscription implements SubscriptionInterface
{
    /** @var Manager */
    protected $manager;

    /** @var Notifications\Manager */
    protected $notificationsManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var PostSubscriptions\Manager */
    protected $postSubscriptionsManager;

    /** @var Block\Manager */
    protected $blockManager;

    /** @var Logger */
    protected $logger;

    /** @var Core\Config */
    protected $config;

    public function __construct(
        Manager $manager = null,
        Notifications\Manager $notificationsManager = null,
        EntitiesBuilder $entitiesBuilder = null,
        PostSubscriptions\Manager $postSubscriptionsManager = null,
        Block\Manager $blockManager = null,
        Config $config = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Comments\Manager');
        $this->notificationsManager = $notificationsManager ?? Di::_()->get('Notifications\Manager');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->postSubscriptionsManager = $postSubscriptionsManager ?? new PostSubscriptions\Manager();
        $this->blockManager = $blockManager ?: Di::_()->get('Security\Block\Manager');
        // $this->logger = $logger ?? Di::_()->get('Logger');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'comment-notifications';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new ActionEventsTopic();
    }

    /**
     * @return string
     */
    public function getTopicRegex(): string
    {
        return 'comment'; // We just want comment events
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        if ($event->getTimestamp() < time() - 3600) {
            // Don't notify for event older than 1 hour, here
            return true;
        }
    
        /** @var User */
        $user = $event->getUser();

        /** @var EntityInterface */
        $entity = $event->getEntity();

        /** @var Comment */
        $comment = $this->manager->getByUrn($event->getActionData()['comment_urn']);

        if (!$comment) {
            return true; // Comment probably deleted
        }

        $isReply = $comment->getPartitionPath() !== '0:0:0';

        if ($entity->type === 'group' && !$isReply) {
            return true; // If this is a group conversation, and not a reply don't notify
        }

        $parent = null;
        $subscribers = [];

        if ($isReply) {
            $parent_guids = explode(':', $comment->getPartitionPath());

            $parent_guid = "{$parent_guids[0]}";
            $parent_path = "0:0:0";
            if ($parent_guids[1] != 0) {
                $parent_guid = $parent_guids[1];
                $parent_path = $comment->getParentPath();
            }

            $luid = $comment->getLuid();
            $luid->setPartitionPath($parent_path);
            $luid->setGuid($parent_guid);

            /** @var Comment */
            $parent = $this->entitiesBuilder->single($luid);
            if ($parent && $parent->getOwnerGuid() != $comment->getOwnerGuid()) {
                $subscribers = [ $parent->getOwnerGuid() ];
            }
        } else {
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
        }

        if (empty($subscribers)) {
            return true; // No one is subscribe
        }

        foreach ($subscribers as $subscriber) {
            $notification = new Notification();

            $notification->setFromGuid((string) $user->getGuid());
            $notification->setEntityUrn($isReply && $parent ? $parent->getUrn() : $entity->getUrn());
            $notification->setType(NotificationTypes::TYPE_COMMENT);
            $notification->setToGuid($subscriber);
            $notification->setData([
                'comment_urn' => $comment->getUrn(),
                'is_reply' => $isReply,
            ]);

            // Save and submit
            if ($this->notificationsManager->add($notification)) {
                // Some logging
                error_log("{$notification->getUuid()} {$notification->getType()} to {$notification->getToGuid()} saved");
                //$this->logger->info("{$notification->getUuid()} {$notification->getType()} to {$notification->getToGuid()} saved");
            }
        }

        return true;
    }
}
