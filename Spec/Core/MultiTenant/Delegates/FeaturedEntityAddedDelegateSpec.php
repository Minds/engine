<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Delegates;

use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Delegates\FeaturedEntityAddedDelegate;
use Minds\Core\MultiTenant\Types\FeaturedEntity;
use Minds\Core\MultiTenant\Types\FeaturedGroup;
use Minds\Core\MultiTenant\Types\FeaturedUser;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class FeaturedEntityAddedDelegateSpec extends ObjectBehavior
{
    protected Collaborator $actionEventsTopic;
    protected Collaborator $entitiesBuilder;
    protected Collaborator $logger;

    public function let(
        ActionEventsTopic $actionEventsTopic,
        EntitiesBuilder $entitiesBuilder,
        Logger $logger
    ) {
        $this->actionEventsTopic = $actionEventsTopic;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->logger = $logger;

        $this->beConstructedWith($actionEventsTopic, $entitiesBuilder, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FeaturedEntityAddedDelegate::class);
    }

    public function it_should_send_action_event_when_valid_user_entity_added(
        User $user,
        User $loggedInUser,
    ) {
        $featuredEntity = new FeaturedUser(
            tenantId: 1,
            entityGuid: 123,
            autoSubscribe: true,
            recommended: true,
            autoPostSubscription: false,
            username: 'testuser',
            name: 'Test User'
        );

        $this->entitiesBuilder->single('123')
            ->shouldBeCalled()
            ->willReturn($user);

        $this->actionEventsTopic->send(Argument::that(function (ActionEvent $actionEvent) use ($featuredEntity, $loggedInUser, $user) {
            return $actionEvent->getAction() === ActionEvent::ACTION_FEATURED_ENTITY_ADDED
                && $actionEvent->getActionData() === [
                    'featured_entity_data' => $featuredEntity
                ];
        }))->shouldBeCalled();

        $this->onAdd($featuredEntity, $loggedInUser);
    }

    public function it_should_send_action_event_when_valid_group_entity_added(
        Group $group,
        User $loggedInUser,
        FeaturedEntity $featuredEntity
    ) {
        $featuredEntity = new FeaturedGroup(
            tenantId: 1,
            entityGuid: 456,
            autoSubscribe: true,
            recommended: true,
            autoPostSubscription: false,
            name: 'Test Group'
        );

        $this->entitiesBuilder->single('456')->willReturn($group);

        $this->actionEventsTopic->send(Argument::that(function (ActionEvent $actionEvent) use ($featuredEntity, $loggedInUser, $group) {
            return $actionEvent->getAction() === ActionEvent::ACTION_FEATURED_ENTITY_ADDED
                && $actionEvent->getActionData() === [
                    'featured_entity_data' => $featuredEntity
                ];
        }))->shouldBeCalled();

        $this->onAdd($featuredEntity, $loggedInUser);
    }

    public function it_should_not_send_action_event_when_invalid_entity_added(
        User $loggedInUser,
        FeaturedEntity $featuredEntity
    ) {
        $featuredEntity = new FeaturedEntity(
            tenantId: 1,
            entityGuid: 789,
            autoSubscribe: true,
            recommended: true,
            autoPostSubscription: false,
            name: 'Test Entity'
        );

        $this->entitiesBuilder->single('789')->willReturn(null);

        $this->logger->error("Valid featured entity not found: 789")->shouldBeCalled();
        $this->actionEventsTopic->send(Argument::any())->shouldNotBeCalled();

        $this->onAdd($featuredEntity, $loggedInUser);
    }
}
