<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\EventStreams;

use Minds\Core\Config\Config;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\EventStreams\FeaturedEntitySyncSubscription;
use Minds\Core\MultiTenant\Services\FeaturedEntityAutoSubscribeService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FeaturedEntitySyncSubscriptionSpec extends ObjectBehavior
{
    protected $multiTenantUsersService;
    protected $featuredEntityAutoSubscribeService;
    protected $logger;
    protected $config;

    public function let(
        TenantUsersService $multiTenantUsersService,
        FeaturedEntityAutoSubscribeService $featuredEntityAutoSubscribeService,
        Logger $logger,
        Config $config
    ) {
        $this->multiTenantUsersService = $multiTenantUsersService;
        $this->featuredEntityAutoSubscribeService = $featuredEntityAutoSubscribeService;
        $this->logger = $logger;
        $this->config = $config;

        $this->beConstructedWith($multiTenantUsersService, $featuredEntityAutoSubscribeService, $logger, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FeaturedEntitySyncSubscription::class);
    }

    public function it_should_return_subscription_id()
    {
        $this->getSubscriptionId()->shouldReturn('featured-entity-sync');
    }

    public function it_should_return_topic()
    {
        $this->getTopic()->shouldBeAnInstanceOf(ActionEventsTopic::class);
    }

    public function it_should_return_topic_regex()
    {
        $this->getTopicRegex()->shouldReturn(ActionEvent::ACTION_FEATURED_ENTITY_ADDED);
    }

    public function it_should_consume_featured_user_added_event(
        ActionEvent $event,
        User $featuredUserEntity,
        User $user1,
        User $user2
    ) {
        $event->getAction()->willReturn(ActionEvent::ACTION_FEATURED_ENTITY_ADDED);
        $event->getEntity()->willReturn($featuredUserEntity);
        $event->getActionData()->willReturn([
            'featured_entity_data' => [
                'tenantId' => 1,
                'entityGuid' => 123456789,
                'autoSubscribe' => true,
                'recommended' => true,
                'autoPostSubscription' => true,
                'username' => 'testuser',
                'name' => 'Test User'
            ]
        ]);

        $featuredUserEntity->getType()->willReturn('user');

        $this->config->get('tenant_id')->willReturn(1);

        $this->multiTenantUsersService->getUsers(tenantId: 1)->willReturn([$user1, $user2]);

        $this->featuredEntityAutoSubscribeService->handleFeaturedUser(Argument::any(), $user1)->shouldBeCalled();
        $this->featuredEntityAutoSubscribeService->handleFeaturedUser(Argument::any(), $user2)->shouldBeCalled();

        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_consume_featured_group_added_event(
        ActionEvent $event,
        Group $group,
        User $user1,
        User $user2
    ) {
        $event->getAction()->willReturn(ActionEvent::ACTION_FEATURED_ENTITY_ADDED);
        $event->getEntity()->willReturn($group);
        $event->getActionData()->willReturn([
            'featured_entity_data' => [
                'tenantId' => 1,
                'entityGuid' => 987654321,
                'autoSubscribe' => true,
                'recommended' => true,
                'autoPostSubscription' => true,
                'name' => 'Test Group'
            ]
        ]);

        $group->getType()->willReturn('group');

        $this->config->get('tenant_id')->willReturn(1);

        $this->multiTenantUsersService->getUsers(tenantId: 1)->willReturn([$user1, $user2]);

        $this->featuredEntityAutoSubscribeService->handleFeaturedGroup(Argument::any(), $user1)->shouldBeCalled();
        $this->featuredEntityAutoSubscribeService->handleFeaturedGroup(Argument::any(), $user2)->shouldBeCalled();

        $this->logger->info(Argument::type('string'))->shouldBeCalled();

        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_handle_unsupported_entity_type(
        ActionEvent $event,
        User $user
    ) {
        $event->getAction()->willReturn(ActionEvent::ACTION_FEATURED_ENTITY_ADDED);
        $event->getEntity()->willReturn($user);
        $event->getActionData()->willReturn(['featured_entity_data' => []]);

        $user->getType()->willReturn('other');

        $this->consume($event)->shouldReturn(true);
    }
}
