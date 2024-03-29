<?php

namespace Spec\Minds\Core\Security\Rbac\Entities;

use Minds\Core\Blogs\Blog;
use Minds\Core\Security\Rbac\Entities\Manager;
use Minds\Core\Security\Rbac\Entities\EntityPermissions;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\Call;
use Minds\Core\Entities\Actions\Save;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\Image;

class ManagerSpec extends ObjectBehavior
{
    /** @var User */
    protected $user;
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;
    /** @var Call */
    protected $db;
    /** @var Save */
    protected $save;

    public function let(
        EntitiesBuilder $entitiesBuilder,
        Call $db,
        Save $save
    ) {
        $this->entitiesBuilder = $entitiesBuilder;
        $this->db = $db;
        $this->save = $save;
        $this->beConstructedWith($this->entitiesBuilder, $this->db, $this->save);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_save_entity_permissions(Activity $entity, Image $image)
    {
        $permissions = new EntityPermissions();
        $entity->setAllowComments(true)->shouldBeCalled();
        $entity->get('entity_guid')->shouldBeCalled()->willReturn(false);
        $entity->getGUID()->shouldBeCalled()->willReturn(1);
        $entity->getType()->shouldBeCalled()->willReturn('activity');
        $this->db->getRow('activity:entitylink:1')->shouldBeCalled()->willReturn([]);
        $this->save->setEntity($entity)->shouldBeCalled()->willReturn($this->save);
        $this->save->save()->shouldBeCalled();
        $this->save($entity, $permissions);
    }

    public function it_should_save_attachment_permissions(Activity $entity, Image $image)
    {
        $permissions = new EntityPermissions();
        $image->setAllowComments(true)->shouldBeCalled();
        $this->entitiesBuilder->single(1)->shouldBeCalled()->willReturn($image);
        $entity->setAllowComments(true)->shouldBeCalled();
        $entity->getGUID()->shouldBeCalled()->willReturn(1);
        $entity->getType()->shouldBeCalled()->willReturn('activity');
        $entity->get('entity_guid')->shouldBeCalled()->willReturn(1);
        $this->db->getRow('activity:entitylink:1')->shouldBeCalled()->willReturn([]);
        $this->save->setEntity($entity)->shouldBeCalled()->willReturn($this->save);
        $this->save->setEntity($image)->shouldBeCalled()->willReturn($this->save);
        $this->save->save()->shouldBeCalled();
        $this->save($entity, $permissions);
    }

    public function it_should_save_linked_entity_permissions(Activity $entity, Blog $parent)
    {
        $permissions = new EntityPermissions();
        $parent->setAllowComments(true)->shouldBeCalled();
        $this->db->getRow('activity:entitylink:1')->shouldBeCalled()
            ->willReturn([2 => $parent]);
        $this->entitiesBuilder->single(1)->shouldBeCalled()->willReturn($parent);
        $entity->setAllowComments(true)->shouldBeCalled();
        $entity->getGUID()->shouldBeCalled()->willReturn(1);
        $entity->getType()->shouldBeCalled()->willReturn('activity');
        $entity->get('entity_guid')->shouldBeCalled()->willReturn(1);
        $this->db->getRow('activity:entitylink:1')->shouldBeCalled()->willReturn([]);
        $this->save->setEntity($entity)->shouldBeCalled()->willReturn($this->save);
        $this->save->setEntity($parent)->shouldBeCalled()->willReturn($this->save);
        $this->save->save()->shouldBeCalled();
        $this->save($entity, $permissions);
    }
}
