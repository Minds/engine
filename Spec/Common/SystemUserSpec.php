<?php
declare(strict_types=1);

namespace Spec\Minds\Common;

use Minds\Common\SystemUser;
use PhpSpec\ObjectBehavior;

class SystemUserSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SystemUser::class);
    }

    public function it_should_use_default_guid_when_no_config_override_available(): void
    {
        $this->getGUID()->shouldReturn(SystemUser::GUID);
    }
}
