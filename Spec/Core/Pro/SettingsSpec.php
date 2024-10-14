<?php

namespace Spec\Minds\Core\Pro;

use Minds\Core\Pro\Settings;
use PhpSpec\ObjectBehavior;

class SettingsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Settings::class);
    }

    public function it_should_export()
    {
        $this
            ->export()
            ->shouldBeArray();
    }
}
