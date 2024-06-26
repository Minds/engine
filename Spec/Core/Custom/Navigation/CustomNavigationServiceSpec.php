<?php

namespace Spec\Minds\Core\Custom\Navigation;

use Minds\Core\Config\Config;
use Minds\Core\Custom\Navigation\CustomNavigationService;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Core\Custom\Navigation\NavigationItem;
use Minds\Core\Custom\Navigation\Repository;
use Minds\Core\Data\cache\PsrWrapper;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class CustomNavigationServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $cacheMock;
    private Collaborator $configMock;

    public function let(
        Repository $repositoryMock,
        PsrWrapper $cacheMock,
        Config $configMock
    ) {
        $this->beConstructedWith($repositoryMock, $cacheMock, $configMock);
        $this->repositoryMock = $repositoryMock;
        $this->cacheMock = $cacheMock;
        $this->configMock = $configMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CustomNavigationService::class);
    }

    public function it_should_return_static_list_for_minds()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(null);

        $items = $this->getItems();
        $items->shouldHaveCount(8);
        $items->shouldBeLike([
            new NavigationItem(
                id: 'newsfeed',
                name: 'Newsfeed',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: true,
                iconId: 'home',
                path: '/newsfeed',
                order: 1,
            ),
            new NavigationItem(
                id: 'explore',
                name: 'Explore',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: true,
                iconId: 'tag',
                path: '/discovery',
                order: 2,
            ),
            new NavigationItem(
                id: 'boost',
                name: 'Boost',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: false,
                iconId: 'trending_up',
                path: '/boost/boost-console',
                order: 3,
            ),
            new NavigationItem(
                id: 'groups',
                name: 'Groups',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: true,
                iconId: 'group',
                path: '/groups',
                url: null,
                action: null,
                order: 4,
            ),
            new NavigationItem(
                id: 'chat',
                name: 'Chat',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: true,
                iconId: 'chat_bubble',
                path: '/chat/rooms',
                url: null,
                action: null,
                order: 5,
            ),
            new NavigationItem(
                id: 'memberships',
                name: 'Memberships',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: false,
                iconId: 'verified',
                path: '/memberships',
                url: null,
                action: null,
                order: 6,
            ),
            new NavigationItem(
                id: 'admin',
                name: 'Admin',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: false,
                iconId: 'dashboard',
                path: '/network/admin',
                order: 7
            ),
            new NavigationItem(
                id: 'channel',
                name: '',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: true,
                iconId: '',
                order: 8,
            ),
        ]);
    }

    public function it_should_return_list_from_database_for_tenants()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);


        $this->repositoryMock->getItems()
            ->willReturn([
                new NavigationItem(
                    id: 'about',
                    name: 'About',
                    type: NavigationItemTypeEnum::CUSTOM_LINK,
                    visible: true,
                    visibleMobile: true,
                    iconId: 'home',
                    url: '/about',
                ),
            ]);

        $items = $this->getItems();
        $items->shouldHaveCount(9);

        $items[8]->id->shouldBe('about');
    }

    public function it_should_return_list_from_database_for_tenants_from_cache()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->cacheMock->get(Argument::any())
            ->willReturn(serialize([
                new NavigationItem(
                    id: 'about',
                    name: 'About',
                    type: NavigationItemTypeEnum::CUSTOM_LINK,
                    visible: true,
                    visibleMobile: true,
                    iconId: 'home',
                    url: '/about',
                ),
            ]));

        $this->repositoryMock->getItems()
            ->shouldNotBeCalled();

        $items = $this->getItems();
        $items->shouldHaveCount(9);

        $items[8]->id->shouldBe('about');
    }

    public function it_should_merge_default_and_database_items_for_tenants()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->repositoryMock->getItems()
            ->willReturn([
                new NavigationItem(
                    id: 'explore',
                    name: 'Global',
                    type: NavigationItemTypeEnum::CORE,
                    visible: true,
                    visibleMobile: true,
                    iconId: 'explore',
                    path: '/explore',
                    order: 2,
                ),
            ]);

        $items = $this->getItems();
        $items->shouldHaveCount(8);

        $items[1]->id->shouldBe('explore');
        $items[1]->name->shouldBe('Global');
    }

    public function it_should_add_a_new_item()
    {
        $this->repositoryMock->addItem(Argument::type(NavigationItem::class))
            ->willReturn(true);

        $this->cacheMock->delete(Argument::type('string'))
            ->shouldBeCalled();

        $this->addItem(new NavigationItem(
            id: 'explore',
            name: 'Global',
            type: NavigationItemTypeEnum::CORE,
            visible: true,
            visibleMobile: true,
            iconId: 'explore',
            path: '/explore',
        ))->shouldBe(true);
    }

    public function it_should_update_the_order_of_items()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->repositoryMock->getItems()
            ->willReturn([
                new NavigationItem(
                    id: 'newsfeed',
                    name: 'Newsfeed',
                    type: NavigationItemTypeEnum::CORE,
                    visible: true,
                    visibleMobile: true,
                    iconId: 'home',
                    path: '/newsfeed',
                    order: 3,
                ),
                new NavigationItem(
                    id: 'explore',
                    name: 'Global',
                    type: NavigationItemTypeEnum::CORE,
                    visible: true,
                    visibleMobile: true,
                    iconId: 'explore',
                    path: '/explore',
                    order: 2,
                ),
            ]);


        $this->repositoryMock->beginTransaction()->shouldBeCalled();

        $this->repositoryMock->addItem(Argument::that(function (NavigationItem $item) {
            return ($item->id === 'newsfeed' && $item->order === 0) || ($item->id === 'explore' &&  $item->order ===1);
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repositoryMock->commitTransaction()->shouldBeCalled();

        $this->cacheMock->set(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalled();

        $this->cacheMock->delete(Argument::type('string'))
            ->shouldBeCalled();

        $this->updateItemsOrder([ 'newsfeed','explore',])
            ->shouldBe(true);
    }


    public function it_should_delete_an_item()
    {
        $this->repositoryMock->deleteItem('newsfeed')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cacheMock->delete(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->deleteItem('newsfeed')
            ->shouldBe(true);
    }
}
