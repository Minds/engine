<?php

namespace Spec\Minds\Core\Custom\Navigation;

use Minds\Core\Config\Config;
use Minds\Core\Custom\Navigation\Enums\NavigationItemActionEnum;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Core\Custom\Navigation\NavigationItem;
use Minds\Core\Custom\Navigation\Repository;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Data\MySQL;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;
    
    public function let(
        MySQL\Client $mysqlClientMock,
        Logger $loggerMock,
        PsrWrapper $cacheMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock,
    ) {
        $this->beConstructedWith($mysqlClientMock, Di::_()->get(Config::class), $loggerMock, $cacheMock);

        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->shouldBeCalled()
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->shouldBeCalled()
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_return_a_list_of_navigation_items(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($stmtMock);

        $stmtMock->execute([ 'tenant_id' => -1 ])
            ->shouldBeCalled()
            ->willReturn(true);

        $stmtMock->rowCount()
            ->willReturn(3);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                
                    'id' => 'about',
                    'name' => 'About',
                    'type' => 'CUSTOM_LINK',
                    'visible' => true,
                    'icon_id' => 'info',
                    'path' => null,
                    'url' => '/about',
                    'action' => null,
                ],
                [
                
                    'id' => 'about-2',
                    'name' => 'About 2',
                    'type' => 'CORE',
                    'visible' => true,
                    'icon_id' => 'info',
                    'path' => '/about-2',
                    'url' => null,
                    'action' => null,
                ],
                [
                
                    'id' => 'more',
                    'name' => 'Extra',
                    'type' => 'CUSTOM_LINK',
                    'visible' => true,
                    'icon_id' => 'more',
                    'path' => null,
                    'url' => null,
                    'action' => 'SHOW_SIDEBAR_MORE',
                ]
            ]);

        $list = $this->getItems();
        $list->shouldHaveCount(3);

        $list[0]->id->shouldBe('about');
        $list[0]->name->shouldBe('About');
        $list[0]->type->shouldBe(NavigationItemTypeEnum::CUSTOM_LINK);
        $list[0]->visible->shouldBe(true);
        $list[0]->iconId->shouldBe('info');
        $list[0]->path->shouldBe(null);
        $list[0]->url->shouldBe('/about');
        $list[0]->action->shouldBe(null);

        $list[1]->path->shouldBe('/about-2');

        $list[2]->action->shouldBe(NavigationItemActionEnum::SHOW_SIDEBAR_MORE);
    }

    public function it_should_add_a_new_item_to_the_list(PDOStatement $stmtMock)
    {
        $this->mysqlMasterMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'tenant_id' => -1,
            'id' => 'newsfeed',
            'name' => 'Newsfeed',
            'type' => 'CORE',
            'visible' => true,
            'icon_id' => 'home',
            'path' => '/newsfeed',
            'url' => null,
            'action' => null,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $item = new NavigationItem(
            id: 'newsfeed',
            name: 'Newsfeed',
            type: NavigationItemTypeEnum::CORE,
            visible: true,
            iconId: 'home',
            path: '/newsfeed',
        );
        $this->addItem($item)->shouldBe(true);
    }

    public function it_should_add_a_new_item_to_the_list_that_has_a_custom_link(PDOStatement $stmtMock)
    {
        $this->mysqlMasterMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'tenant_id' => -1,
            'id' => 'newsfeed',
            'name' => 'Newsfeed',
            'type' => 'CUSTOM_LINK',
            'visible' => true,
            'icon_id' => 'home',
            'path' => null,
            'url' => null,
            'action' => 'SHOW_SIDEBAR_MORE',
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $item = new NavigationItem(
            id: 'newsfeed',
            name: 'Newsfeed',
            type: NavigationItemTypeEnum::CUSTOM_LINK,
            visible: true,
            iconId: 'home',
            action: NavigationItemActionEnum::SHOW_SIDEBAR_MORE
        );
        $this->addItem($item)->shouldBe(true);
    }

    public function it_should_update_an_existing_item(PDOStatement $stmtMock)
    {
        $this->mysqlMasterMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'tenant_id' => -1,
            'id' => 'newsfeed',
            'name' => 'Newsfeed',
            'type' => 'CORE',
            'visible' => true,
            'icon_id' => 'home',
            'path' => '/newsfeed',
            'url' => null,
            'action' => null,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $item = new NavigationItem(
            id: 'newsfeed',
            name: 'Newsfeed',
            type: NavigationItemTypeEnum::CORE,
            visible: true,
            iconId: 'home',
            path: '/newsfeed',
        );
        $this->updateItem($item)->shouldBe(true);
    }

}
