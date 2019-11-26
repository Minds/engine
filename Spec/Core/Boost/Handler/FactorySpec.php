<?php

namespace Spec\Minds\Core\Boost\Handler;

use Minds\Core\Boost\Handler\Factory;
use Minds\Core\Boost\Handler\Newsfeed;
use PhpSpec\ObjectBehavior;

class FactorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Factory::class);
    }

    public function it_should_return_a_handler()
    {
        $this::get(Factory::HANDLER_NEWSFEED)->shouldHaveType(Newsfeed::class);
    }

    public function it_should_throw_an_error_if_handler_doesnt_exist()
    {
        $this->shouldThrow("\Exception")->during("get", ["FakeBoost"]);
    }
}
