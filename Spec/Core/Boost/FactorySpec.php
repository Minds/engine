<?php

namespace Spec\Minds\Core\Boost;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Data\MongoDB\Client as MongoClient;

class FactorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Boost\Factory');
    }

    public function it_should_build_a_handler(MongoClient $db)
    {
        $this::build("Newsfeed", Argument::any(), $db)->shouldHaveType('Minds\Core\Boost\Newsfeed');
    }

    public function it_should_throw_an_error_if_handler_doesnt_exist()
    {
        $this->shouldThrow("\Exception")->during("build", ["FakeBoost"]);
    }
}
