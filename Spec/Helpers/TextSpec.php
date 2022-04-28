<?php

namespace Spec\Minds\Helpers;

use Minds\Helpers\Text;
use PhpSpec\ObjectBehavior;

class TextSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Text::class);
    }

    public function it_should_truncate_string()
    {
        $this::truncate("Hello world, this should be truncate", 14)
            ->shouldBe('Hello world...');
    }

    public function it_should_NOT_truncate_string()
    {
        $this::truncate("Hello world, this should be truncate", 140)
            ->shouldBe("Hello world, this should be truncate");
    }
}
