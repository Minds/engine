<?php

namespace Spec\Minds\Core\Entities\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Repositories\MySQLRepository;
use PDO;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class MySQLRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    function let(
        Client $mysqlClientMock,
        Config $configMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock,
    )
    {
        $this->beConstructedWith($configMock, $mysqlClientMock, Di::_()->get('Logger'));

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

    function it_is_initializable()
    {
        $this->shouldHaveType(MySQLRepository::class);
    }

    function it_should_load_an_entity_from_a_guid()
    {

    }

    function it_should_not_load_an_entity_from_a_guid_that_does_not_exist()
    {

    }

    function it_should_load_a_user_from_their_username()
    {

    }

    function it_should_not_load_a_user_from_their_username_if_not_found()
    {

    }

    // Create

    function it_should_create_an_activity()
    {

    }

    function it_should_create_an_image()
    {

    }

    function it_should_create_a_video()
    {

    }

    function it_should_create_a_group()
    {

    }

    function it_should_create_a_user()
    {

    }

    // Update

    function it_should_update_an_activity()
    {

    }

    function it_should_update_an_image()
    {

    }

    function it_should_update_a_video()
    {

    }

    function it_should_update_a_group()
    {

    }

    function it_should_update_a_user()
    {

    }

    // Delete

    function it_should_delete_an_activity()
    {

    }

    function it_should_delete_an_image()
    {

    }

    function it_should_delete_a_video()
    {

    }

    function it_should_delete_a_group()
    {

    }

    function it_should_delete_a_user()
    {

    }
}
