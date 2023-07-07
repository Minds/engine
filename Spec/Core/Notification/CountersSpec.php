<?php

namespace Spec\Minds\Core\Notification;

use Minds\Core\Notification\Counters;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CountersSpec extends ObjectBehavior
{
    protected $sql;

    protected $features;

    public function let(
        \PDO $sql
    ) {
        $this->sql = $sql;

        $this->beConstructedWith($sql);
    }
    public function it_is_initializable()
    {
        $this->shouldHaveType(Counters::class);
    }
}
