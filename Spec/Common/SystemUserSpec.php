<?php
declare(strict_types=1);

namespace Spec\Minds\Common;

use Minds\Common\SystemUser;
use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;

class SystemUserSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SystemUser::class);
    }

    public function it_should_use_default_guid_when_no_config_override_available(): void
    {
        $this->getGUID()->shouldReturn(SystemUser::DEFAULT_GUID);
    }

    public function it_should_use_config_system_user_guid_when_config_override_available(
        Config $mindsConfig
    ): void {
        $mindsConfig->get('system_user')
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->beConstructedWith($mindsConfig);

        $this->getGUID()->shouldReturn('123');
    }
}
