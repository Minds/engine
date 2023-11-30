<?php

namespace Spec\Minds\Core\Entities\Repositories;

use Minds\Core\Data\Call;
use Minds\Core\Data\Cassandra\Thrift\Indexes;
use Minds\Core\Data\lookup;
use Minds\Core\Entities\Repositories\CassandraRepository;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class CassandraRepositorySpec extends ObjectBehavior
{
    private Collaborator $entitiesTableMock;
    private Collaborator $lookupTableMock;
    private Collaborator $indexesTableMock;

    public function let(Call $entitiesTableMock, lookup $lookupTableMock, Indexes $indexesTableMock)
    {
        $this->beConstructedWith($entitiesTableMock, $lookupTableMock, $indexesTableMock);

        $this->entitiesTableMock = $entitiesTableMock;
        $this->lookupTableMock = $lookupTableMock;
        $this->indexesTableMock = $indexesTableMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CassandraRepository::class);
    }

    public function it_should_load_an_entity_from_a_guid()
    {
        $this->entitiesTableMock->getRow(123, Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                'type' => 'activity',
            ]);

        $entity = $this->loadFromGuid(123);
        $entity->getGuid()->shouldBe('123');
    }

    public function it_should_not_load_an_entity_from_a_guid_that_does_not_exist()
    {
        $this->entitiesTableMock->getRow(123, Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $this->loadFromGuid(123)->shouldBe(null);
    }

    public function it_should_load_a_user_from_their_username()
    {
        $this->lookupTableMock->get('minds')
            ->willReturn(['123' => time()]);

        $this->entitiesTableMock->getRow(123, Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                'type' => 'user',
                'username' => 'minds',
            ]);
        
        $user = $this->loadFromIndex('username', 'minds');
        $user->getGuid()->shouldBe('123');
    }

    public function it_should_not_load_a_user_from_their_username_if_not_found()
    {
        $this->lookupTableMock->get('minds')
            ->willReturn(null);

        $this->loadFromIndex('username', 'minds')->shouldBe(null);
    }

    // Create

    public function it_should_create_an_activity()
    {
        $activity = new Activity();
        $activity->guid = 123;

        $this->entitiesTableMock->insert(123, Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->insert(Argument::any(), [ 123 => time() ])
            ->shouldBeCalled();

        $this->create($activity)->shouldBe(true);
    }

    public function it_should_create_an_image()
    {
        $image = new Image();
        $image->guid = 123;

        $this->entitiesTableMock->insert(123, Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->insert(Argument::any(), [ 123 => time() ])
            ->shouldBeCalled();

        $this->create($image)->shouldBe(true);
    }

    public function it_should_create_a_video()
    {
        $video = new Video();
        $video->guid = 123;

        $this->entitiesTableMock->insert(123, Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->insert(Argument::any(), [ 123 => time() ])
            ->shouldBeCalled();

        $this->create($video)->shouldBe(true);
    }

    public function it_should_create_a_group()
    {
        $group = new Group();
        $group->setGuid(123);

        $this->entitiesTableMock->insert(123, Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->insert(Argument::any(), [ 123 => time() ])
            ->shouldBeCalled();

        $this->create($group)->shouldBe(true);
    }

    public function it_should_create_a_user()
    {
        $user = new User();
        $user->guid = 123;
        $user->email = 'info@minds.com';

        $this->entitiesTableMock->insert(123, Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->insert(Argument::any(), [ 123 => time() ])
            ->shouldBeCalled();

        $this->create($user)->shouldBe(true);
    }

    // Update

    public function it_should_update_an_activity()
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->message = 'hello';

        $this->entitiesTableMock->insert(123, [
            'message' => 'hello',
            'time_updated' => time(),
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->update($activity, ['message'])->shouldBe(true);
    }

    public function it_should_update_an_image()
    {
        $image = new Image();
        $image->guid = 123;
        $image->width = 10;

        $this->entitiesTableMock->insert(123, [
            'width' => 10,
            'time_updated' => time(),
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->update($image, ['width'])->shouldBe(true);
    }

    public function it_should_update_a_video()
    {
        $video = new Video();
        $video->guid = 123;
        $video->width = 10;

        $this->entitiesTableMock->insert(123, [
            'width' => 10,
            'time_updated' => time(),
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->update($video, ['width'])->shouldBe(true);
    }

    public function it_should_update_a_group()
    {
        $group = new Group();
        $group->setGuid(123);
        $group->setName('My Group Name');

        $this->entitiesTableMock->insert(123, [
            'name' => 'My Group Name',
            'time_updated' => time(),
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->update($group, ['name'])->shouldBe(true);
    }

    public function it_should_update_a_user()
    {
        $user = new User();
        $user->guid = 123;
        $user->setName('minds');

        $this->entitiesTableMock->insert(123, [
            'name' => 'minds',
            'time_updated' => time(),
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->update($user, ['name'])->shouldBe(true);
    }

    // Delete

    public function it_should_delete_an_activity()
    {
        $activity = new Activity();
        $activity->guid = 123;

        $this->entitiesTableMock->removeRow(123)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->remove(Argument::any(), [123])
            ->shouldBeCalled();

        $this->delete($activity)->shouldBe(true);
    }

    public function it_should_delete_an_image()
    {
        $image = new Image();
        $image->guid = 123;

        $this->entitiesTableMock->removeRow(123)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->remove(Argument::any(), [123])
            ->shouldBeCalled();

        $this->delete($image)->shouldBe(true);
    }

    public function it_should_delete_a_video()
    {
        $video = new Video();
        $video->guid = 123;

        $this->entitiesTableMock->removeRow(123)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->remove(Argument::any(), [123])
            ->shouldBeCalled();

        $this->delete($video)->shouldBe(true);
    }

    public function it_should_delete_a_group()
    {
        $group = new Group();
        $group->setGuid(123);

        $this->entitiesTableMock->removeRow(123)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->remove(Argument::any(), [123])
            ->shouldBeCalled();

        $this->delete($group)->shouldBe(true);
    }

    public function it_should_delete_a_user()
    {
        $user = new User();
        $user->guid = 123;
        $user->username = 'minds';
        $user->email = 'info@minds.com';

        $this->entitiesTableMock->removeRow(123)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexesTableMock->remove(Argument::any(), [123])
            ->shouldBeCalled();

        $this->lookupTableMock->get('minds')
            ->shouldBeCalled()
            ->willReturn(123);
        $this->lookupTableMock->remove('minds')
            ->shouldBeCalledOnce();
        $this->lookupTableMock->remove('info@minds.com')
            ->shouldBeCalledOnce();

        $this->delete($user)->shouldBe(true);
    }

}
