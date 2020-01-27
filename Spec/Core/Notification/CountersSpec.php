<?php

namespace Spec\Minds\Core\Notification;

use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Core\Notification\Counters;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CountersSpec extends ObjectBehavior
{
    protected $sql;

    protected $features;

    public function let(
        \PDO $sql,
        FeaturesManager $features
    ) {
        $this->sql = $sql;
        $this->features = $features;

        $this->beConstructedWith($sql, $features);
    }
    public function it_is_initializable()
    {
        $this->shouldHaveType(Counters::class);
    }
}
