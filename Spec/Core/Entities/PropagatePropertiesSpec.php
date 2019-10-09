<?php

namespace Spec\Minds\Core\Entities;

use Minds\Core\Data\Call;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use PhpSpec\ObjectBehavior;

class PropagatePropertiesSpec extends ObjectBehavior
{
    protected $db;
    protected $save;
    protected $entitiesBuilder;
    protected $propagator;
    protected $activity;
    protected $entity;

    public function let(
        Call $db,
        Save $save,
        EntitiesBuilder $entitiesBuilder,
        Activity $activity,
        Entity $entity
    ) {
        $this->beConstructedWith($db, $save, $entitiesBuilder);
        $this->db = $db;
        $this->save = $save;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->activity = $activity;
        $this->entity = $entity;
        $this->clearPropogators();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Entities\PropagateProperties');
    }

    public function it_should_call_from_activity()
    {
        $this->activity->get('entity_guid')->shouldBeCalled()->willReturn(1001);
        $this->entitiesBuilder->single(1001)->shouldBeCalled()->willReturn($this->entity);

        $this->from($this->activity);
    }

    public function it_should_call_to_activities()
    {
        $this->entity->getGUID()->shouldBeCalled()->willReturn(1002);
        $this->db->getRow("activity:entitylink:1002")->shouldBeCalled()->willReturn([1001 => 12345]);
        $this->entitiesBuilder->single(1001)->shouldBeCalled()->willReturn($this->activity);

        $this->from($this->entity);
    }
}
