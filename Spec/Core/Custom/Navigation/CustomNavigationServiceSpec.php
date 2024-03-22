<?php

namespace Spec\Minds\Core\Custom\Navigation;

use Minds\Core\Config\Config;
use Minds\Core\Custom\Navigation\CustomNavigationService;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Core\Custom\Navigation\NavigationItem;
use Minds\Core\Custom\Navigation\Repository;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class CustomNavigationServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $configMock;

    public function let(
        Repository $repositoryMock,
        Config $configMock
    ) {
        $this->beConstructedWith($repositoryMock, $configMock);
        $this->repositoryMock = $repositoryMock;
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
        $items->shouldHaveCount(6);
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
                    iconId: 'home',
                    url: '/about',
                ),
            ]);

        $items = $this->getItems();
        $items->shouldHaveCount(7);

        $items[6]->id->shouldBe('about');
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
                    iconId: 'explore',
                    path: '/explore',
                ),
            ]);

        $items = $this->getItems();
        $items->shouldHaveCount(6);

        $items[1]->id->shouldBe('explore');
        $items[1]->name->shouldBe('Global');
    }

    public function it_should_add_a_new_item()
    {
        $this->repositoryMock->addItem(Argument::type(NavigationItem::class))
            ->willReturn(true);
        
        $this->addItem(new NavigationItem(
            id: 'explore',
            name: 'Global',
            type: NavigationItemTypeEnum::CORE,
            visible: true,
            iconId: 'explore',
            path: '/explore',
        ))->shouldBe(true);
    }

    public function it_should_update_a_new_item()
    {
        $this->repositoryMock->updateItem(Argument::type(NavigationItem::class))
            ->willReturn(true);
        
        $this->updateItem(new NavigationItem(
            id: 'explore',
            name: 'Global',
            type: NavigationItemTypeEnum::CORE,
            visible: true,
            iconId: 'explore',
            path: '/explore',
        ))->shouldBe(true);
    }
}
