<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Manager as GroupsMembershipManager;
use Minds\Core\MultiTenant\Services\FeaturedEntityAutoSubscribeService;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\MultiTenant\Types\FeaturedUser;
use Minds\Core\MultiTenant\Types\FeaturedGroup;
use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Services\PostSubscriptionsService;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class FeaturedEntityAutoSubscribeServiceSpec extends ObjectBehavior
{
    private Collaborator $featuredEntityService;
    private Collaborator $postSubscriptionsService;
    private Collaborator $groupsMembershipManager;
    private Collaborator $entitiesBuilder;

    public function let(
        FeaturedEntityService $featuredEntityService,
        PostSubscriptionsService $postSubscriptionsService,
        GroupsMembershipManager $groupsMembershipManager,
        EntitiesBuilder $entitiesBuilder
    )
    {
        $this->featuredEntityService = $featuredEntityService;
        $this->postSubscriptionsService = $postSubscriptionsService;
        $this->groupsMembershipManager = $groupsMembershipManager;
        $this->entitiesBuilder = $entitiesBuilder;
        
        $this->beConstructedWith(
            $this->featuredEntityService,
            $this->postSubscriptionsService,
            $this->groupsMembershipManager,
            $this->entitiesBuilder
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FeaturedEntityAutoSubscribeService::class);
    }

    // Users

    public function it_should_auto_subscribe_to_featured_users(
        User $subject,
        User $user1,
        User $user2
    ) {
        $tenantId = 123;
        $featuredUser1 = new FeaturedUser(
            tenantId: 123,
            entityGuid: 1234567891,
            autoSubscribe: true,
            recommended: true,
            autoPostSubscription: false,
            username: 'username1',
            name: 'name1'
        );
        $featuredUser2 = new FeaturedUser(
            tenantId: 123,
            entityGuid: 1234567892,
            autoSubscribe: true,
            recommended: true,
            autoPostSubscription: false,
            username: 'username2',
            name: 'name2'
        );

        $this->featuredEntityService->getAllFeaturedEntities($tenantId)
            ->shouldBeCalled()
            ->willReturn([$featuredUser1, $featuredUser2]);

        $this->entitiesBuilder->single($featuredUser1->entityGuid)
            ->shouldNotBeCalled();

        $this->postSubscriptionsService->withEntity(Argument::any())
            ->shouldNotBeCalled();

        $this->postSubscriptionsService->withUser($subject)
            ->shouldNotBeCalled();

        $this->postSubscriptionsService->subscribe(
            PostSubscriptionFrequencyEnum::ALWAYS
        )->shouldNotBeCalled();

        $this->autoSubscribe(
            $subject,
            123
        );
    }

    public function it_should_auto_subscribe_to_featured_users_and_auto_subscribe_to_posts(
        User $subject,
        User $user1,
        User $user2
    ) {
        $tenantId = 123;
        $featuredUser1 = new FeaturedUser(
            tenantId: 123,
            entityGuid: 1234567891,
            autoSubscribe: true,
            recommended: true,
            autoPostSubscription: true,
            username: 'username1',
            name: 'name1'
        );
        $featuredUser2 = new FeaturedUser(
            tenantId: 123,
            entityGuid: 1234567892,
            autoSubscribe: true,
            recommended: true,
            autoPostSubscription: true,
            username: 'username2',
            name: 'name2'
        );

        $this->featuredEntityService->getAllFeaturedEntities($tenantId)
            ->shouldBeCalled()
            ->willReturn([$featuredUser1, $featuredUser2]);

        $subject->subscribe($featuredUser1->entityGuid)
            ->shouldBeCalled();

        $subject->subscribe($featuredUser2->entityGuid)
            ->shouldBeCalled();

        $this->entitiesBuilder->single($featuredUser1->entityGuid)
            ->shouldBeCalled()
            ->willReturn($user1);

        $this->entitiesBuilder->single($featuredUser2->entityGuid)
            ->shouldBeCalled()
            ->willReturn($user2);

        $this->postSubscriptionsService->withEntity($user1)
            ->shouldBeCalled()
            ->willReturn($this->postSubscriptionsService);

        $this->postSubscriptionsService->withEntity($user2)
            ->shouldBeCalled()
            ->willReturn($this->postSubscriptionsService);

        $this->postSubscriptionsService->withUser($subject)
            ->shouldBeCalled()
            ->willReturn($this->postSubscriptionsService);
            
        $this->postSubscriptionsService->subscribe(
            PostSubscriptionFrequencyEnum::ALWAYS
        )->shouldBeCalled();

        $this->autoSubscribe(
            $subject,
            123
        );
    }

    public function it_should_NOT_auto_subscribe_to_featured_users_when_auto_subscribe_is_false(
        User $subject,
        User $user1,
        User $user2
    ) {
        $tenantId = 123;
        $featuredUser1 = new FeaturedUser(
            tenantId: 123,
            entityGuid: 1234567891,
            autoSubscribe: false,
            recommended: true,
            autoPostSubscription: false,
            username: 'username1',
            name: 'name1'
        );
        $featuredUser2 = new FeaturedUser(
            tenantId: 123,
            entityGuid: 1234567892,
            autoSubscribe: false,
            recommended: true,
            autoPostSubscription: false,
            username: 'username2',
            name: 'name2'
        );

        $subject->subscribe($featuredUser1->entityGuid)
            ->shouldNotBeCalled();

        $subject->subscribe($featuredUser2->entityGuid)
            ->shouldNotBeCalled();

        $this->featuredEntityService->getAllFeaturedEntities($tenantId)
            ->shouldBeCalled()
            ->willReturn([$featuredUser1, $featuredUser2]);

        $this->entitiesBuilder->single($featuredUser1->entityGuid)
            ->shouldNotBeCalled();

        $this->postSubscriptionsService->withEntity(Argument::any())
            ->shouldNotBeCalled();

        $this->postSubscriptionsService->withUser($subject)
            ->shouldNotBeCalled();

        $this->postSubscriptionsService->subscribe(
            PostSubscriptionFrequencyEnum::ALWAYS
        )->shouldNotBeCalled();

        $this->autoSubscribe(
            $subject,
            123
        );
    }

    // Groups

    public function it_should_auto_subscribe_to_featured_groups(
        User $subject,
        Group $group1,
        Group $group2
    ) {
        $tenantId = 123;
        $featuredGroup1 = new FeaturedGroup(
            tenantId: 123,
            entityGuid: 1234567891,
            autoSubscribe: true,
            recommended: true,
            name: 'name1'
        );
        $featuredGroup2 = new FeaturedGroup(
            tenantId: 123,
            entityGuid: 1234567892,
            autoSubscribe: true,
            recommended: true,
            name: 'name2'
        );

        $this->featuredEntityService->getAllFeaturedEntities($tenantId)
            ->shouldBeCalled()
            ->willReturn([$featuredGroup1, $featuredGroup2]);

        $this->entitiesBuilder->single($featuredGroup1->entityGuid)
            ->shouldBeCalled()
            ->willReturn($group1);

        $this->entitiesBuilder->single($featuredGroup2->entityGuid)
            ->shouldBeCalled()
            ->willReturn($group2);

        $this->groupsMembershipManager->joinGroup(
            group: $group1,
            user: $subject,
            membershipLevel: GroupMembershipLevelEnum::MEMBER
        )->shouldBeCalled();

        $this->groupsMembershipManager->joinGroup(
            group: $group2,
            user: $subject,
            membershipLevel: GroupMembershipLevelEnum::MEMBER
        )->shouldBeCalled();

        $this->autoSubscribe(
            $subject,
            123
        );
    }

    public function it_should_NOT_auto_subscribe_to_featured_groups_when_auto_subscribe_is_false(
        User $subject
    ) {
        $tenantId = 123;
        $featuredGroup1 = new FeaturedGroup(
            tenantId: 123,
            entityGuid: 1234567891,
            autoSubscribe: false,
            recommended: true,
            name: 'name1'
        );
        $featuredGroup2 = new FeaturedGroup(
            tenantId: 123,
            entityGuid: 1234567892,
            autoSubscribe: false,
            recommended: true,
            name: 'name2'
        );

        $this->featuredEntityService->getAllFeaturedEntities($tenantId)
            ->shouldBeCalled()
            ->willReturn([$featuredGroup1, $featuredGroup2]);

        $this->entitiesBuilder->single($featuredGroup1->entityGuid)
            ->shouldNotBeCalled();

        $this->entitiesBuilder->single($featuredGroup2->entityGuid)
            ->shouldNotBeCalled();

        $this->groupsMembershipManager->joinGroup(
            group: Argument::any(),
            user: $subject,
            membershipLevel: GroupMembershipLevelEnum::MEMBER
        )->shouldNotBeCalled();

        $this->autoSubscribe(
            $subject,
            123
        );
    }
}
