<?php

namespace Spec\Minds\Core\Comments;

use Minds\Common\Repository\Response;
use Minds\Core\Comments\Comment;
use Minds\Core\Comments\CommentNotificationsEventStreamsSubscription;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications;
use Minds\Core\Comments\Manager;
use Minds\Core\Security\Block;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Luid;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notification\PostSubscriptions;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;

class CommentNotificationsEventStreamsSubscriptionSpec extends ObjectBehavior
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

    public function let(
        Manager $manager,
        Notifications\Manager $notificationsManager,
        EntitiesBuilder $entitiesBuilder,
        PostSubscriptions\Manager $postSubscriptionsManager,
        Block\Manager $blockManager,
        Config $config
    ) {
        $this->beConstructedWith($manager, $notificationsManager, $entitiesBuilder, $postSubscriptionsManager, $blockManager, $config);
        $this->manager = $manager;
        $this->notificationsManager = $notificationsManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->postSubscriptionsManager = $postSubscriptionsManager;
        $this->blockManager = $blockManager;
        // $this->logger = $logger;
        $this->config = $config;
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(CommentNotificationsEventStreamsSubscription::class);
    }

    public function it_should_send_comment(ActionEvent $actionEvent, User $user, Comment $comment)
    {
        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getUser()
            ->willReturn($user);

        $actionEvent->getActionData()
            ->willReturn([
                'comment_urn' => 'urn:comment:123:0:0:0:456'
            ]);

        $activity = new Activity();

        $actionEvent->getEntity()
            ->willReturn($activity);

        $this->manager->getByUrn('urn:comment:123:0:0:0:456')
            ->willReturn($comment);

        $comment->getPartitionPath()
            ->willReturn('0:0:0');

        $comment->getOwnerGuid()
            ->willReturn('888');

        $comment->getUrn()
            ->willReturn('comment-urn');

        $comment->getEntityGuid()
            ->willReturn('123');

        $this->postSubscriptionsManager->setEntityGuid('123')
            ->willReturn($this->postSubscriptionsManager);

        $this->postSubscriptionsManager->getFollowers()
            ->willReturn(new Response([ '999' ]));


        $this->blockManager->hasBlocked(Argument::that(function ($blockEntry) {
            return true;
        }))
            ->willReturn(false);

        $this->notificationsManager->add(Argument::that(function (Notification $notification) {
            return $notification->getData()['is_reply'] === false;
        }))
            ->shouldBeCalled()
            ->willReturn(true);
    
        $this->consume($actionEvent)
            ->shouldBe(true);
    }

    public function it_should_send_comment_reply(ActionEvent $actionEvent, User $user, Activity $activity, Comment $comment, Comment $parentComment)
    {
        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getUser()
            ->willReturn($user);

        $actionEvent->getActionData()
            ->willReturn([
                'comment_urn' => 'urn:comment:123:456:0:0:789'
            ]);

        $actionEvent->getEntity()
            ->willReturn($activity);

        $this->manager->getByUrn('urn:comment:123:456:0:0:789')
            ->willReturn($comment);

        $comment->getPartitionPath()
            ->willReturn('456:0:0');

        $comment->getLuid()
            ->willReturn(new Luid());

        $comment->getOwnerGuid()
            ->willReturn('888');

        $comment->getUrn()
            ->willReturn('this-is-a-urn');

        $this->entitiesBuilder->single(Argument::any())
            ->willReturn($parentComment);

        $parentComment->getOwnerGuid()
            ->willReturn('999');

        $parentComment->getUrn()
            ->willReturn('this-is-a-urn');

        $this->notificationsManager->add(Argument::that(function (Notification $notification) {
            return $notification->getData()['is_reply'] === true;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent)
            ->shouldBe(true);
    }
}
