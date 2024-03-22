<?php

namespace Spec\Minds\Core\Custom\Navigation\Controllers;

use Minds\Core\Custom\Navigation\Controllers\NavigationController;
use Minds\Core\Custom\Navigation\CustomNavigationService;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Core\Custom\Navigation\NavigationItem;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

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
}