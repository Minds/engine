<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Chat\Services;

use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Block\Manager as BlockManager;
use Minds\Core\Subscriptions\Relational\Repository as SubscriptionsRepository;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class RoomServiceSpec extends ObjectBehavior
{
    private Collaborator $roomRepositoryMock;
    private Collaborator $subscriptionsRepositoryMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $blockManagerMock;

    public function let(
        RoomRepository $roomRepository,
        SubscriptionsRepository $subscriptionsRepository,
        EntitiesBuilder $entitiesBuilder,
        BlockManager $blockManager
    ): void {
        $this->roomRepositoryMock = $roomRepository;
        $this->subscriptionsRepositoryMock = $subscriptionsRepository;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->blockManagerMock = $blockManager;
        $this->beConstructedWith(
            $this->roomRepositoryMock,
            $this->subscriptionsRepositoryMock,
            $this->entitiesBuilderMock,
            $this->blockManagerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(RoomService::class);
    }

    public function it_should_create_one_to_one_chat_room(): void
    {
        
    }
}
