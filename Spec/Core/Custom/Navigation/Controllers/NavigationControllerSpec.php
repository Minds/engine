<?php

namespace Spec\Minds\Core\Custom\Navigation\Controllers;

use Minds\Core\Custom\Navigation\Controllers\NavigationController;
use Minds\Core\Custom\Navigation\CustomNavigationService;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Core\Custom\Navigation\NavigationItem;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class NavigationControllerSpec extends ObjectBehavior
{
    private Collaborator $serviceMock;

    public function let(CustomNavigationService $serviceMock)
    {
        $this->beConstructedWith($serviceMock);
        $this->serviceMock = $serviceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NavigationController::class);
    }

    public function it_should_return_a_list_of_item()
    {
        $this->serviceMock->getItems()
            ->willReturn([
                new NavigationItem(
                    id: 'explore',
                    name: 'Global',
                    type: NavigationItemTypeEnum::CORE,
                    visible: true,
                    iconId: 'explore',
                    path: '/explore',
                ),
            ]);
        $this->getCustomNavigationItems()->shouldHaveCount(1);
    }

    public function it_should_upsert_an_item()
    {
        $this->serviceMock->addItem(Argument::type(NavigationItem::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $result = $this->upsertCustomNavigationItem(
            id: 'explore',
            name: 'Global',
            type: NavigationItemTypeEnum::CORE,
            visible: true,
            iconId: 'explore',
            path: '/explore',
            order: 2,
        );
        $result->id->shouldBe('explore');
    }

    public function it_should_update_the_order_of_items()
    {
        $this->serviceMock->updateItemsOrder(['newsfeed','explore','more','about'])
            ->willReturn(true);

        $this->serviceMock->getItems()
            ->willReturn([
                new NavigationItem(
                    id: 'explore',
                    name: 'Global',
                    type: NavigationItemTypeEnum::CORE,
                    visible: true,
                    iconId: 'explore',
                    path: '/explore',
                ),
                new NavigationItem(
                    id: 'explore',
                    name: 'Global',
                    type: NavigationItemTypeEnum::CORE,
                    visible: true,
                    iconId: 'explore',
                    path: '/explore',
                ),
                new NavigationItem(
                    id: 'explore',
                    name: 'Global',
                    type: NavigationItemTypeEnum::CORE,
                    visible: true,
                    iconId: 'explore',
                    path: '/explore',
                ),
                new NavigationItem(
                    id: 'explore',
                    name: 'Global',
                    type: NavigationItemTypeEnum::CORE,
                    visible: true,
                    iconId: 'explore',
                    path: '/explore',
                ),
            ]);
        $response = $this->updateCustomNavigationItemsOrder(['newsfeed','explore','more','about']);
        $response->shouldHaveCount(4);
    }

    
    public function it_should_return_server_error_if_update_of_order_fails()
    {
        $this->serviceMock->updateItemsOrder(['newsfeed','explore','more','about'])
            ->willReturn(false);

        $this->serviceMock->getItems()
            ->shouldNotBeCalled();
    
        $this->shouldThrow(ServerErrorException::class)->duringUpdateCustomNavigationItemsOrder(['newsfeed','explore','more','about']);
    }
}
