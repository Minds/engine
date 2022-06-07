<?php

namespace Spec\Minds\Core\Entities\Ops;

use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use PhpSpec\ObjectBehavior;

class EntitiesOpsTopicSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(EntitiesOpsTopic::class);
    }

    public function it_should_send_event()
    {
        $event = new EntitiesOpsEvent();
        $event->setEntityUrn('urn:entity:123')
            ->setOp('create');

        $this->send($event)
            ->shouldBe(true);
    }
}
