<?php

namespace Spec\Minds\Core\Payments;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Payments\Subscriptions;
use Minds\Core\Payments\HookInterface;

class HooksSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Payments\Hooks');
    }

    public function it_should_call_a_function_of_all_hooks()
    {
        $this->callMe(['foo'=>'bar'])->shouldReturn($this);
    }

    public function it_should_register_a_hook(HookInterface $hook)
    {
        $this->register($hook)->shouldReturn($this);
    }
}
