<?php

namespace Spec\Minds\Core\Notifications\PostSubscriptions\Helpers;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager as GroupsMembershipManager;
use Minds\Core\Groups\V2\Membership\Membership;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Helpers\PostNotificationDispatchHelper;
use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class PostNotificationDispatchHelperSpec extends ObjectBehavior
{
    private Collaborator $groupsMembershipManager;
    private Collaborator $entitiesBuilder;
    private Collaborator $logger;

    public function let(
        GroupsMembershipManager $groupsMembershipManager,
        EntitiesBuilder $entitiesBuilder,
        Logger $logger
    ) {
        $this->beConstructedWith(
            $groupsMembershipManager,
            $entitiesBuilder,
            $logger
        );

        $this->groupsMembershipManager = $groupsMembershipManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->logger = $logger;
    }
  
    public function it_is_initializable()
    {
        $this->shouldHaveType(PostNotificationDispatchHelper::class);
    }

    public function it_should_determine_if_a_post_notification_can_be_dispatched_because_the_entity_has_no_container(
        Entity $forActivity
    ) {
        $userGuid = 2234567890123456;
        $entityGuid = 3234567890123456;
        $frequency = PostSubscriptionFrequencyEnum::ALWAYS;

        $postSubscription = new PostSubscription(
            userGuid: $userGuid,
            entityGuid: $entityGuid,
            frequency: $frequency
        );

        $forActivity->getTimeCreated()
          ->willReturn(time());

        $forActivity->getContainerGuid()
          ->shouldBeCalled()
          ->willReturn(null);

        $this->canDispatch($postSubscription, $forActivity)->shouldBe(true);
    }

    public function it_should_determine_if_a_post_notification_can_be_dispatched_because_the_entity_container_is_not_a_group(
        Entity $forActivity,
        Activity $container
    ) {
        $containerGuid = 1234567890123456;
        $userGuid = 2234567890123456;
        $entityGuid = 3234567890123456;
        $frequency = PostSubscriptionFrequencyEnum::ALWAYS;

        $postSubscription = new PostSubscription(
            userGuid: $userGuid,
            entityGuid: $entityGuid,
            frequency: $frequency
        );
      
        $forActivity->getTimeCreated()
          ->willReturn(time());

        $forActivity->getContainerGuid()
          ->shouldBeCalled()
          ->willReturn($containerGuid);

        $this->entitiesBuilder->single($containerGuid)
          ->shouldBeCalled()
          ->willReturn($container);

        $this->canDispatch($postSubscription, $forActivity)->shouldBe(true);
    }

    public function it_should_determine_if_a_post_notification_can_be_dispatched_because_the_container_entity_is_a_group_and_the_user_is_a_member(
        Entity $forActivity,
        Group $container,
        User $recipient,
        Membership $membership
    ) {
        $containerGuid = 1234567890123456;
        $userGuid = 2234567890123456;
        $entityGuid = 3234567890123456;
        $frequency = PostSubscriptionFrequencyEnum::ALWAYS;

        $postSubscription = new PostSubscription(
            userGuid: $userGuid,
            entityGuid: $entityGuid,
            frequency: $frequency
        );
        
        $forActivity->getTimeCreated()
          ->willReturn(time());

        $forActivity->getContainerGuid()
          ->shouldBeCalled()
          ->willReturn($containerGuid);

        $this->entitiesBuilder->single($containerGuid)
          ->shouldBeCalled()
          ->willReturn($container);

        $this->entitiesBuilder->single($userGuid)
          ->shouldBeCalled()
          ->willReturn($recipient);

        $membership->isMember()
          ->shouldBeCalled()
          ->willReturn(true);

        $this->groupsMembershipManager->getMembership($container, $recipient)
          ->shouldBeCalled()
          ->willReturn($membership);

        $this->canDispatch($postSubscription, $forActivity)->shouldBe(true);
    }

    public function it_should_determine_if_a_post_notification_can_NOT_be_dispatched_because_the_container_entity_is_a_group_and_the_user_is_NOT_a_member(
        Entity $forActivity,
        Group $container,
        User $recipient,
        Membership $membership
    ) {
        $containerGuid = 1234567890123456;
        $userGuid = 2234567890123456;
        $entityGuid = 3234567890123456;
        $frequency = PostSubscriptionFrequencyEnum::ALWAYS;

        $postSubscription = new PostSubscription(
            userGuid: $userGuid,
            entityGuid: $entityGuid,
            frequency: $frequency
        );
        
        $forActivity->getTimeCreated()
          ->willReturn(time());

        $forActivity->getContainerGuid()
          ->shouldBeCalled()
          ->willReturn($containerGuid);

        $this->entitiesBuilder->single($containerGuid)
          ->shouldBeCalled()
          ->willReturn($container);

        $this->entitiesBuilder->single($userGuid)
          ->shouldBeCalled()
          ->willReturn($recipient);

        $membership->isMember()
          ->shouldBeCalled()
          ->willReturn(false);

        $this->groupsMembershipManager->getMembership($container, $recipient)
          ->shouldBeCalled()
          ->willReturn($membership);

        $this->canDispatch($postSubscription, $forActivity)->shouldBe(false);
    }

    public function it_should_not_allow_a_post_that_is_older_than_24_hours_old(Entity $forActivity)
    {
        $userGuid = 2234567890123456;
        $entityGuid = 3234567890123456;
        $frequency = PostSubscriptionFrequencyEnum::ALWAYS;

        $postSubscription = new PostSubscription(
            userGuid: $userGuid,
            entityGuid: $entityGuid,
            frequency: $frequency
        );

        $forActivity->getTimeCreated()
          ->willReturn(strtotime('2 days ago'));

        $this->canDispatch($postSubscription, $forActivity)->shouldBe(false);
    }
}
