<?php

namespace Spec\Minds\Core\Permissions\Delegates;

use Minds\Entities\Activity;
use Minds\Entities\Entity;
use PhpSpec\ObjectBehavior;

class PropagatePropertiesSpec extends ObjectBehavior
{
    /** @var Entity */
    protected $entity;
    /** @var Activity */
    protected $activity;

    public function let(
        Entity $entity,
        Activity $activity
    ) {
        $this->entity = $entity;
        $this->activity = $activity;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Permissions\Delegates\PropagateProperties');
    }

    public function it_should_propagate_changes_to_activity()
    {
        $this->entity->getAllowComments()->shouldBeCalled()->willReturn(true);
        $this->activity->getAllowComments()->shouldBeCalled()->willReturn(false);
        $this->activity->setAllowComments(true)->shouldBeCalled();

        $this->toActivity($this->entity, $this->activity);
    }

    public function it_should_propogate_properties_from_activity()
    {
        $this->activity->getAllowComments()->shouldBeCalled()->willReturn(true);
        $this->entity->getAllowComments()->shouldBeCalled()->willReturn(false);
        $this->entity->setAllowComments(true)->shouldBeCalled();

        $this->fromActivity($this->activity, $this->entity);
    }
}
