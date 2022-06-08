<?php

namespace Spec\Minds\Core\Entities\Ops;

use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;

class EntitiesOpsEventSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(EntitiesOpsEvent::class);
    }

    public function it_should_set_and_get_entity_urn()
    {
        $this->setEntityUrn('urn:user:123');

        $this->getEntityUrn()
            ->shouldBe('urn:user:123');
    }

    public function it_should_set_and_get_op()
    {
        $this->setOp('create');

        $this->getOp()
            ->shouldBe('create');
    }

    public function it_should_throw_invalid_ops()
    {
        $this->shouldThrow(ServerErrorException::class)->duringSetOp('createe');
    }
}
